<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\Etudiant;
use App\Entity\ParentProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ParentOtpAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $urlGen
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'parent_verify_code'
            && $request->isMethod('POST');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $session = $request->getSession();
        $data    = $session?->get('otp.parent', []) ?? [];
        $otp     = (string) $request->request->get('otp', '');

        if (!$data || ($data['expires_at'] ?? 0) < time() || $otp !== (string) ($data['code'] ?? '')) {
            throw new AuthenticationException('Invalid or expired code.');
        }

        $digits = static fn (?string $s) => preg_replace('/\D+/', '', (string) $s);

        $childId = (int) ($data['child_id'] ?? 0);
        $phone   = (string) ($data['phone'] ?? '');
        $name    = (string) ($data['name'] ?? 'Parent');

        /** @var Etudiant|null $child */
        $child = $childId ? $this->em->getRepository(Etudiant::class)->find($childId) : null;
        if (!$child) {
            throw new AuthenticationException('Child not found.');
        }

        // Find or create user/profile by phone
        $profile = $this->em->getRepository(ParentProfile::class)->findOneBy(['phone' => $phone]);
        if ($profile) {
            $user = $profile->getUser();
        } else {
            $user = new User();
            // If email is non-nullable/unique, generate a stable placeholder from phone
            $safePhone = $digits($phone) ?: uniqid();
            $user->setEmail(sprintf('parent+%s@noemail.local', $safePhone));
            $user->setRoles(['ROLE_PARENT']);
            $user->setPassword('!otp'); // not used with OTP
            $this->em->persist($user);

            $profile = new ParentProfile();
            $profile->setUser($user);
            $profile->setFullName($name);
            $profile->setPhone($phone);
            $this->em->persist($profile);
        }

        // Ensure the link to the selected child exists
        $profile->addChild($child);
        $this->em->flush();

        // One-time use → remove OTP from session
        $session?->remove('otp.parent');

        // Log the user in
        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn() => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->urlGen->generate('parent_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Be friendly to static analysis and older stubs:
        $session = $request->getSession();
        if ($session instanceof SessionInterface) {
            /** @var FlashBagInterface $flashes */
            $flashes = $session->getBag('flashes');
            $flashes->add('danger', 'Code invalide ou expiré.');
        }

        return new RedirectResponse($this->urlGen->generate('app_login'));
    }
}
