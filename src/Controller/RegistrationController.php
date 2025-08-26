<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Etudiant;
use App\Entity\ParentProfile;
use App\Form\RegistrationFormType;        // staff form (existing)
use App\Form\ParentRegisterFormType;      // parent form (new)
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

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

        // --- Staff form (existing) ---
        $staffUser = new User();
        $staffForm = $this->createForm(RegistrationFormType::class, $staffUser);
        $staffForm->handleRequest($request);

        if ($staffForm->isSubmitted() && $request->request->has('register_staff')) {
            if ($staffForm->isValid()) {
                $plain = (string) $staffForm->get('plainPassword')->getData();
                $staffUser->setPassword($hasher->hashPassword($staffUser, $plain));
                $staffUser->setRoles(['ROLE_ADMIN']); // adapt to your staff role

                $em->persist($staffUser);
                $em->flush();

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
        }

        // --- Parent form ---
        $parentForm = $this->createForm(ParentRegisterFormType::class);
        $parentForm->handleRequest($request);

        if ($parentForm->isSubmitted() && $request->request->has('register_parent')) {
            // Run our custom validation FIRST (regardless of isValid)
            /** @var Etudiant|null $child */
            $child = $parentForm->get('child')->getData();

            $hasCustomError = false;

            $normName = static function (?string $s): string {
                return preg_replace('/\s+/', ' ', mb_strtolower(trim((string)$s)));
            };
            $digits = static function (?string $s): string {
                return preg_replace('/\D+/', '', (string)$s);
            };

            $inputFullName = $normName($parentForm->get('fullName')->getData());
            $inputPhone    = $digits($parentForm->get('phone')->getData());

            $allowedNames = array_filter([
                $normName($child?->getNomPere()),
                $normName($child?->getNomMere()),
            ], static fn($v) => $v !== '');

            $allowedPhones = array_values(array_filter([
                $digits($child?->getNumTel()),
                $digits($child?->getNumTel2()),
            ], static fn($v) => $v !== ''));

            if (!$child) {
                $parentForm->get('child')->addError(new FormError('Sélectionnez votre enfant.'));
                $hasCustomError = true;
            }

            if (!\in_array($inputFullName, $allowedNames, true)) {
                $parentForm->get('fullName')->addError(new FormError(
                    'Le nom saisi doit correspondre au père ou à la mère enregistrés pour cet élève.'
                ));
                $hasCustomError = true;
            }

            if (!\in_array($inputPhone, $allowedPhones, true)) {
                $parentForm->get('phone')->addError(new FormError(
                    'Le téléphone doit correspondre à l’un des numéros enregistrés pour cet élève.'
                ));
                $hasCustomError = true;
            }

            // If Symfony base constraints OR custom checks fail → re-render with errors
            if (!$parentForm->isValid() || $hasCustomError) {
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $staffForm->createView(),
                    'parentForm'       => $parentForm->createView(),
                    'classes'          => $em->getRepository(Etudiant::class)
                        ->createQueryBuilder('e')
                        ->select('DISTINCT e.classe')
                        ->getQuery()
                        ->getSingleColumnResult(),
                ]);
            }

            // --- All good: create user and profile ---
            $user = new User();
            $user->setEmail((string) $parentForm->get('email')->getData());
            $user->setRoles(['ROLE_PARENT']);
            $user->setPassword(
                $hasher->hashPassword($user, (string) $parentForm->get('plainPassword')->getData())
            );
            $em->persist($user);

            $profile = new ParentProfile();
            $profile->setUser($user);
            $profile->setFullName((string) $parentForm->get('fullName')->getData());
            $profile->setPhone((string) $parentForm->get('phone')->getData());
            $profile->addChild($child);

            $em->persist($profile);
            $em->flush();

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
            'classes'          => $em->getRepository(Etudiant::class)
                ->createQueryBuilder('e')
                ->select('DISTINCT e.classe')
                ->getQuery()
                ->getSingleColumnResult(),
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

        return $this->isGranted('ROLE_PARENT')
            ? $this->redirectToRoute('parent_home')
            : $this->redirectToRoute('app_admin');
    }
}
