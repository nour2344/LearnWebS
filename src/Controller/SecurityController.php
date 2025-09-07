<?php

namespace App\Controller;

use App\Entity\Etudiant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    // Allow GET (render form) and POST (handled by AppAuthenticator)
    #[Route(path: '/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, AuthenticationUtils $authUtils): Response
    {
        // Admin login helpers (avoid Twig "undefined" errors)
        $error        = $authUtils->getLastAuthenticationError();
        $lastUsername = $authUtils->getLastUsername();

        $classes = $this->fetchDistinctClasses();

        // Read OTP and/or draft from session
        $otp      = $request->getSession()->get('otp.parent', []);
        $draft    = $request->getSession()->get('otp.parent_draft', []);
        $otpValid = $otp && (($otp['expires_at'] ?? 0) > time());

        // If the stored OTP expired, clear it
        if ($otp && !$otpValid) {
            $request->getSession()->remove('otp.parent');
            $otp = [];
        }

        // Data used to pre-fill the Parent form:
        // - prefer the pending OTP payload (read-only prefill)
        // - otherwise, use the last draft (values typed before validation failed)
        $otpData    = $otpValid ? $otp : $draft;
        $otpPending = (bool) $otpValid;

        // Open the Parent tab automatically if we have a draft or a pending OTP
        $showParent = $otpPending || !empty($draft);

        return $this->render('security/login.html.twig', [
            'error'         => $error,
            'last_username' => $lastUsername,
            'classes'       => $classes,
            'otp_pending'   => $otpPending,
            'show_parent'   => $showParent ? true : null,
            'otp_data'      => $otpData,
        ]);
    }

    /**
     * Step 1: Parent posts (fullname, phone, classe, child) to request OTP.
     */
    #[Route(path: '/login/parent/request-code', name: 'parent_request_code', methods: ['POST'])]
    public function parentRequestCode(Request $request): Response
    {
        $normName = static fn (?string $s): string => preg_replace('/\s+/', ' ', mb_strtolower(trim((string) $s)));
        $digits   = static fn (?string $s): string => preg_replace('/\D+/', '', (string) $s);

        // Raw inputs (keep originals to re-fill the form nicely)
        $fullnameRaw = (string) $request->request->get('fullname', '');
        $phoneRaw    = (string) $request->request->get('phone', '');
        $classe      = (string) $request->request->get('classe', '');
        $childId     = (string) $request->request->get('child', '');

        // Save a draft so the form is repopulated after redirect on error
        $request->getSession()->set('otp.parent_draft', [
            'name'     => $fullnameRaw,
            'phone'    => $phoneRaw,
            'classe'   => $classe,
            'child_id' => $childId !== '' ? (int) $childId : null,
        ]);

        // Normalized values for validation
        $fullname = $normName($fullnameRaw);
        $phone    = $digits($phoneRaw);

        // Basic input checks
        if ($fullname === '' || $phone === '' || $classe === '' || $childId === '') {
            $this->addFlash('danger', 'Veuillez remplir tous les champs et sélectionner votre enfant.');
            return $this->redirectToRoute('app_login', ['_fragment' => 'parent']);
        }

        /** @var Etudiant|null $child */
        $child = $this->em->getRepository(Etudiant::class)->find($childId);
        if (!$child) {
            $this->addFlash('danger', 'Élève introuvable.');
            return $this->redirectToRoute('app_login', ['_fragment' => 'parent']);
        }

        $allowedNames = array_filter([
            $normName($child->getNomPere()),
            $normName($child->getNomMere()),
        ], static fn ($v) => $v !== '');

        $allowedPhones = array_values(array_filter([
            $digits($child->getNumTel()),
            $digits($child->getNumTel2()),
        ], static fn ($v) => $v !== ''));

        if ($child->getClasse() !== $classe) {
            $this->addFlash('danger', 'La classe ne correspond pas à celle de l’élève.');
            return $this->redirectToRoute('app_login', ['_fragment' => 'parent']);
        }
        if (!\in_array($fullname, $allowedNames, true)) {
            $this->addFlash('danger', 'Le nom doit correspondre au père ou à la mère de cet élève.');
            return $this->redirectToRoute('app_login', ['_fragment' => 'parent']);
        }
        if (!\in_array($phone, $allowedPhones, true)) {
            $this->addFlash('danger', 'Le téléphone doit être l’un des numéros enregistrés (n°1 ou n°2).');
            return $this->redirectToRoute('app_login', ['_fragment' => 'parent']);
        }

        // Generate a 6-digit code and store it in session (5 minutes)
        $code = random_int(100000, 999999);
        $request->getSession()->set('otp.parent', [
            'code'       => (string) $code,
            'expires_at' => time() + 300,
            'child_id'   => (int) $child->getId(),
            'phone'      => $phoneRaw,       // keep display-friendly version
            'name'       => $fullnameRaw,    // keep display-friendly version
            'classe'     => $classe,
        ]);

        // Clear draft now that we have a valid OTP request
        $request->getSession()->remove('otp.parent_draft');

        // TODO: send real SMS here (Twilio, etc.)
        // DEV ONLY: display the code in a flash so you can test locally
        $this->addFlash('success', sprintf('Code SMS envoyé (DEV): %s', $code));

        return $this->redirectToRoute('app_login', ['_fragment' => 'parent']);
    }

    /**
     * Step 2: Parent submits OTP code.
     * Intercepted by ParentOtpAuthenticator; this is just a placeholder.
     */
    #[Route(path: '/login/parent/verify', name: 'parent_verify_code', methods: ['POST'])]
    public function parentVerifyPlaceholder(): Response
    {
        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method is intercepted by the firewall.');
    }

    /** @return string[] */
    private function fetchDistinctClasses(): array
    {
        $rows = $this->em->getRepository(Etudiant::class)
            ->createQueryBuilder('e')
            ->select('DISTINCT e.classe AS c')
            ->getQuery()
            ->getScalarResult();

        return array_values(array_filter(array_map(static fn($r) => $r['c'] ?? null, $rows)));
    }
}
