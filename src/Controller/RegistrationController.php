<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\ParentProfile;
use App\Form\RegistrationFormType;        // staff form (existing)
use App\Form\ParentRegisterFormType;      // parent form (new)
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;                 // <- missing
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface; // <- missing

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier) {}

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ): Response {
        if ($this->getUser()) {
            // already logged in – bounce to the right area
            return $this->isGranted('ROLE_PARENT')
                ? $this->redirectToRoute('parent_home')
                : $this->redirectToRoute('app_admin');
        }

        // --- Staff form (your existing one) ---
        $staffUser = new User();
        $staffForm = $this->createForm(RegistrationFormType::class, $staffUser);
        $staffForm->handleRequest($request);

        if ($staffForm->isSubmitted() && $staffForm->isValid() && $request->request->has('register_staff')) {
            $plain = (string) $staffForm->get('plainPassword')->getData();
            $staffUser->setPassword($hasher->hashPassword($staffUser, $plain));
            // choose the right role for “staff”
            $staffUser->setRoles(['ROLE_ADMIN']); // or ['ROLE_USER'] if that’s your staff role

            $em->persist($staffUser);
            $em->flush();

            // email verification
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $staffUser,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@example.test', 'Gestion Étudiants'))
                    ->to((string) $staffUser->getEmail())
                    ->subject('Confirmez votre adresse e-mail')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $this->addFlash('success', 'Compte staff créé. Vérifiez votre e-mail.');
            return $this->redirectToRoute('app_login');
        }

        // --- Parent form ---
        $parentForm = $this->createForm(ParentRegisterFormType::class);
        $parentForm->handleRequest($request);

        if ($parentForm->isSubmitted() && $parentForm->isValid() && $request->request->has('register_parent')) {
            // 1) Create the user with ROLE_PARENT
            $user = new User();
            $user->setEmail((string) $parentForm->get('email')->getData());
            $user->setRoles(['ROLE_PARENT']);
            $user->setPassword(
                $hasher->hashPassword($user, (string) $parentForm->get('plainPassword')->getData())
            );
            $em->persist($user);

            // 2) Create ParentProfile and link fields
            $profile = new ParentProfile();
            $profile->setUser($user);
            // make sure these setters exist in ParentProfile
            $profile->setFullName((string) $parentForm->get('fullName')->getData());
            $profile->setPhone((string) $parentForm->get('phone')->getData());

            // 3) Link chosen child (Etudiant)
            $child = $parentForm->get('child')->getData();
            $profile->addChild($child);

            $em->persist($profile);
            $em->flush();

            // 4) Verify email
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@example.test', 'Gestion Étudiants'))
                    ->to($user->getEmail())
                    ->subject('Confirmez votre adresse e-mail')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
                    ->context(['user' => $user])
            );

            $this->addFlash('success', 'Compte parent créé. Vérifiez votre e-mail.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $staffForm->createView(),
            'parentForm'       => $parentForm->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            /** @var User $user */
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('verify_email_error', $translator->trans($e->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email address has been verified.');

        // Redirect by role after verification
        return $this->isGranted('ROLE_PARENT')
            ? $this->redirectToRoute('parent_home')
            : $this->redirectToRoute('app_admin');
    }
}
