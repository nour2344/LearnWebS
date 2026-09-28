<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        if ('json' === $request->getContentTypeFormat()) {
            $data = $request->toArray();

            $email = trim((string) ($data['email'] ?? ''));
            $password = (string) ($data['password'] ?? '');
            $csrf = (string) ($data['_csrf_token'] ?? '');
            $remember = !empty($data['_remember_me']);
        } else {
            $email = trim((string) $request->request->get('email', ''));
            $password = (string) $request->request->get('password', '');
            $csrf = (string) $request->request->get('_csrf_token', '');
            $remember = (bool) $request->request->get('_remember_me', false);
        }

        $request->getSession()->set(
            SecurityRequestAttributes::LAST_USERNAME,
            $email
        );

        $badges = [
            new CsrfTokenBadge('authenticate', $csrf),
        ];

        if ($remember) {
            $badges[] = new RememberMeBadge();
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            $badges
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        $roles = $token->getRoleNames();

        // Admin / Super Admin
        if (
            \in_array('ROLE_ADMIN', $roles, true)
            || \in_array('ROLE_SUPER_ADMIN', $roles, true)
        ) {
           return new RedirectResponse(
    $this->urlGenerator->generate('home')
);
        }

        // Parent
        if (\in_array('ROLE_PARENT', $roles, true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('parent_home')
            );
        }

        // Fallback
        return new RedirectResponse(
            $this->urlGenerator->generate('home')
        );
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}