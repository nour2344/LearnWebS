<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Etudiant;
use App\Entity\ParentProfile;
use App\Form\RegistrationFormType;
use App\Form\ParentRegisterFormType;
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
            return $this->isGranted('ROLE_PARENT')
                ? $this->redirectToRoute('parent_home')
                : $this->redirectToRoute('app_admin');
        }

        // ---------- Staff form
        $staffUser = new User();
        $staffForm = $this->createForm(RegistrationFormType::class, $staffUser);
        $staffForm->handleRequest($request);

        if ($staffForm->isSubmitted() && $request->request->has('register_staff')) {
            if ($staffForm->isValid()) {
                $plain = (string) $staffForm->get('plainPassword')->getData();
                $staffUser->setPassword($hasher->hashPassword($staffUser, $plain));
                $staffUser->setRoles(['ROLE_ADMIN']); // adapte si besoin

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

        // ---------- Parent form
        $parentForm = $this->createForm(ParentRegisterFormType::class);
        $parentForm->handleRequest($request);

        if ($parentForm->isSubmitted() && $request->request->has('register_parent')) {
            /** @var Etudiant|null $child */
            $child = $parentForm->get('child')->getData();

            $hasCustomError = false;

            $normName = static function (?string $s): string {
                return preg_replace('/\s+/', ' ', mb_strtolower(trim((string) $s)));
            };
            $digits = static function (?string $s): string {
                return preg_replace('/\D+/', '', (string) $s);
            };

            $inputFullName = $normName($parentForm->get('fullName')->getData());
            $inputPhone    = $digits($parentForm->get('phone')->getData());
            $chosenClass   = (string) $request->request->get('classeSelect', '');

            $allowedNames = array_filter([
                $normName($child?->getNomPere()),
                $normName($child?->getNomMere()),
            ], static fn ($v) => $v !== '');

            $allowedPhones = array_values(array_filter([
                $digits($child?->getNumTel()),
                $digits($child?->getNumTel2()),
            ], static fn ($v) => $v !== ''));

            if (!$child) {
                $parentForm->get('child')->addError(new FormError('Sélectionnez votre enfant.'));
                $hasCustomError = true;
            }

            if ($child && $chosenClass !== '' && $child->getClasse() !== $chosenClass) {
                $parentForm->get('child')->addError(new FormError(
                    'La classe ne correspond pas à celle de l’élève sélectionné.'
                ));
                $hasCustomError = true;
            }

            if (!\in_array($inputFullName, $allowedNames, true)) {
                $parentForm->get('fullName')->addError(new FormError(
                    'Le nom saisi doit correspondre au père OU à la mère enregistrés pour cet élève.'
                ));
                $hasCustomError = true;
            }

            if (!\in_array($inputPhone, $allowedPhones, true)) {
                $parentForm->get('phone')->addError(new FormError(
                    'Le téléphone doit être l’un des numéros enregistrés (n°1 ou n°2).'
                ));
                $hasCustomError = true;
            }

            // SMS code (démo)
            if ($parentForm->has('smsCode')) {
                $code = (string) $parentForm->get('smsCode')->getData();
                if ($code !== '' && $code !== '1234') {
                    $parentForm->get('smsCode')->addError(new FormError('Code SMS invalide.'));
                    $hasCustomError = true;
                }
            }

            // Doublons
            $email = $parentForm->has('email') ? (string) $parentForm->get('email')->getData() : '';
            if ($email !== '' && $em->getRepository(User::class)->findOneBy(['email' => $email])) {
                $parentForm->get('email')->addError(new FormError('Cet e-mail est déjà utilisé.'));
                $hasCustomError = true;
            }

            $existingPhoneOwner = $em->getRepository(ParentProfile::class)
                ->findOneBy(['phone' => (string) $parentForm->get('phone')->getData()]);
            if ($existingPhoneOwner) {
                $parentForm->get('phone')->addError(new FormError('Un compte parent utilise déjà ce téléphone.'));
                $hasCustomError = true;
            }

            if ($child) {
                $dup = $em->createQuery('
                    SELECT pp.id FROM App\Entity\ParentProfile pp
                    JOIN pp.children c WITH c = :child
                ')
                    ->setParameter('child', $child)
                    ->setMaxResults(1)
                    ->getOneOrNullResult();

                if ($dup) {
                    $parentForm->get('child')->addError(new FormError('Un parent est déjà associé à cet élève.'));
                    $hasCustomError = true;
                }
            }

            if (!$parentForm->isValid() || $hasCustomError) {
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $staffForm->createView(),
                    'parentForm'       => $parentForm->createView(),
                    'classes'          => $this->fetchDistinctClasses($em),
                ]);
            }

            // Création du User + ParentProfile
            $user = new User();
            $user->setEmail($email);
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
            'classes'          => $this->fetchDistinctClasses($em),
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

    /** @return string[] */
    private function fetchDistinctClasses(EntityManagerInterface $em): array
    {
        $rows = $em->getRepository(Etudiant::class)
            ->createQueryBuilder('e')
            ->select('DISTINCT e.classe AS c')
            ->getQuery()
            ->getScalarResult();

        return array_values(array_filter(array_map(static fn($r) => $r['c'] ?? null, $rows)));
    }
}
