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
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator) {}

    public function authenticate(Request $request): Passport
    {
        if ('json' === $request->getContentTypeFormat()) {
            $data     = $request->toArray();
            $email    = (string) ($data['email'] ?? '');
            $password = (string) ($data['password'] ?? '');
            $csrf     = (string) ($data['_csrf_token'] ?? '');
            $remember = !empty($data['_remember_me']);
        } else {
            $email    = (string) $request->request->get('email', '');
            $password = (string) $request->request->get('password', '');
            $csrf     = (string) $request->request->get('_csrf_token', '');
            $remember = (bool) $request->request->get('_remember_me', false);
        }

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        $badges = [ new CsrfTokenBadge('authenticate', $csrf) ];
        if ($remember) {
            $badges[] = new RememberMeBadge();
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            $badges
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $roles = $token->getRoleNames();
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        $path = $targetPath ? (parse_url($targetPath, PHP_URL_PATH) ?? '') : '';

        if (in_array('ROLE_PARENT', $roles, true)) {
            if ($targetPath && str_starts_with($path, '/parent')) {
                return new RedirectResponse($targetPath);
            }
            return new RedirectResponse($this->urlGenerator->generate('parent_home'));
        }

        if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_SUPER_ADMIN', $roles, true)) {
            if ($targetPath && str_starts_with($path, '/')) {
                return new RedirectResponse($targetPath);
            }
            return new RedirectResponse($this->urlGenerator->generate('home'));
        }

        return new RedirectResponse($this->urlGenerator->generate('home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
