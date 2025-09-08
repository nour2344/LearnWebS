<?php
namespace App\Controller;

use App\Entity\SchoolSetting;
use App\Entity\User;
use App\Form\SchoolSettingType;
use App\Repository\SchoolSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\AppAuthenticator;

class SchoolSetupController extends AbstractController
{
    #[Route('/school/setup', name: 'school_setup', methods: ['GET','POST'])]
    public function setup(
        Request $request,
        SchoolSettingRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $session = $request->getSession();

        // Single row (tenant-less)
        $setting = $repo->findOneBy([]) ?? new SchoolSetting();

        $form = $this->createForm(SchoolSettingType::class, $setting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $logo */
            $logo = $form->get('logoFile')->getData();
            if ($logo) {
                $uploads = $this->getParameter('kernel.project_dir') . '/public/uploads/logos';
                (new Filesystem())->mkdir($uploads);
                $ext = $logo->guessExtension() ?: 'bin';
                $filename = 'logo_' . uniqid() . '.' . $ext;
                $logo->move($uploads, $filename);
                $setting->setLogoFilename($filename);
            }

            // Persist setting
            $em->persist($setting);
            $em->flush();

            $this->addFlash('success', 'Établissement enregistré.');
            // If admin is already created, we can go to the dashboard/home
            if ($session->get('setup.admin_created')) {
                return $this->redirectToRoute('home');
            }
            return $this->redirectToRoute('school_setup');
        }

        // OTP + admin state for the wizard
        $otp = $session->get('setup.admin_otp', []);
        $otpPending = $otp && (($otp['expires_at'] ?? 0) > time());
        $adminCreated = (bool) $session->get('setup.admin_created', false);

        return $this->render('school/setup.html.twig', [
            'form'          => $form->createView(),
            'current_logo'  => $setting->getLogoFilename(),
            'otp_pending'   => $otpPending,
            'admin_created' => $adminCreated,
            'admin_email'   => $otpPending ? ($otp['email'] ?? '') : ($session->get('setup.admin_email') ?? ''),
        ]);
    }

    #[Route('/school/setup/admin/send-code', name: 'school_setup_admin_send', methods: ['POST'])]
    public function sendCode(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        if (!$this->isCsrfTokenValid('setup_admin_send', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('school_setup', ['_fragment' => 'admin']);
        }

        $email    = trim((string) $request->request->get('email'));
        $password = (string) $request->request->get('password');
        $confirm  = (string) $request->request->get('password_confirm');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('danger', 'Adresse e-mail invalide.');
            return $this->redirectToRoute('school_setup', ['_fragment' => 'admin']);
        }
        if (strlen($password) < 8) {
            $this->addFlash('danger', 'Mot de passe trop court (min. 8).');
            return $this->redirectToRoute('school_setup', ['_fragment' => 'admin']);
        }
        if ($password !== $confirm) {
            $this->addFlash('danger', 'Les mots de passe ne correspondent pas.');
            return $this->redirectToRoute('school_setup', ['_fragment' => 'admin']);
        }
        if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
            $this->addFlash('danger', 'Un utilisateur existe déjà avec cet e-mail.');
            return $this->redirectToRoute('school_setup', ['_fragment' => 'admin']);
        }

        // Generate OTP and store in session (10 minutes)
        $code = random_int(100000, 999999);
        $request->getSession()->set('setup.admin_otp', [
            'email'      => $email,
            'password'   => $password,
            'code'       => (string) $code,
            'expires_at' => time() + 600,
        ]);
        $request->getSession()->remove('setup.admin_created');
        $request->getSession()->set('setup.admin_email', $email);

        // Send email
        $mail = (new TemplatedEmail())
            ->from(new Address('no-reply@example.test', 'Portail Établissement'))
            ->to($email)
            ->subject('Votre code de vérification')
            ->htmlTemplate('emails/admin_otp.html.twig')
            ->context(['code' => $code]);
        try {
            $mailer->send($mail);
            $this->addFlash('success', 'Code envoyé à votre e-mail.');
        } catch (\Throwable $e) {
            // DEV fallback
            $this->addFlash('success', sprintf('Code (DEV) : %s', $code));
        }

        return $this->redirectToRoute('school_setup', ['_fragment' => 'admin']);
    }

    #[Route('/school/setup/admin/verify', name: 'school_setup_admin_verify', methods: ['POST'])]
    public function verifyCode(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserAuthenticatorInterface $userAuth,
        AppAuthenticator $authenticator
    ): Response {
        if (!$this->isCsrfTokenValid('setup_admin_verify', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('school_setup', ['_fragment' => 'admin']);
        }

        $session = $request->getSession();
        $otp     = $session->get('setup.admin_otp', []);
        $codeIn  = (string) $request->request->get('otp', '');

        if (!$otp || ($otp['expires_at'] ?? 0) < time() || $codeIn !== (string) ($otp['code'] ?? '')) {
            $this->addFlash('danger', 'Code invalide ou expiré.');
            return $this->redirectToRoute('school_setup', ['_fragment' => 'admin']);
        }

        // Create & login admin
        $user = new User();
        $user->setEmail((string) $otp['email']);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($hasher->hashPassword($user, (string) $otp['password']));
        $user->setIsVerified(true);

        $em->persist($user);
        $em->flush();

        // Clear OTP, mark step 1 done
        $session->remove('setup.admin_otp');
        $session->set('setup.admin_created', true);
        $session->set('setup.admin_email', $user->getEmail());

        // Log the user in so step 2 + redirect home work seamlessly
        $userAuth->authenticateUser($user, $authenticator, $request);

        $this->addFlash('success', 'Compte administrateur créé et validé. Passez à l’étape 2.');
        return $this->redirectToRoute('school_setup', ['_fragment' => 'school']);
    }
}
