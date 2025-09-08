<?php

namespace App\Security;

use App\Repository\EtudiantRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ParentIdAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private EtudiantRepository $repo,
        private UrlGeneratorInterface $url,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'parent_id_login'
            && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $ident = trim((string) $request->request->get('identifiant', ''));
        $pwd   = (string) $request->request->get('password', '');
        $csrf  = (string) $request->request->get('_csrf_token', '');

        $student = $this->repo->findOneBy(['loginId' => $ident]);

        if (
            !$student ||
            !$student->getLoginPasswordHash() ||
            !\password_verify($pwd, $student->getLoginPasswordHash())
        ) {
            throw new AuthenticationException('Identifiant ou mot de passe invalide.');
        }

        $userBadge = new UserBadge(
            'student#' . $student->getId(),
            fn () => new ParentStudentUser(
                $student->getId(),
                (string) $student->getLoginId(),
                trim(($student->getNom() ?? '') . ' ' . ($student->getPrenom() ?? ''))
            )
        );

        return new SelfValidatingPassport($userBadge, [
            new CsrfTokenBadge('parent_id_login', $csrf),
            new RememberMeBadge(),
        ]);
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return new RedirectResponse($this->url->generate('parent_home'));
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        // Only if the firewall isn’t stateless and a session exists
        if ($request->hasSession()) {
            $session = $request->getSession();

            // Guard the type for static analysers and safety
            if ($session instanceof SessionInterface) {
                $bag = $session->getBag('flashes'); // SessionBagInterface

                // Make IDE happy: ensure it’s really a FlashBagInterface
                if ($bag instanceof FlashBagInterface) {
                    $bag->add('danger', $exception->getMessage());
                }
            }
        }

        // Show the parent tab again on the login page
        return new RedirectResponse(
            $this->url->generate('app_login', ['show_parent' => 1])
        );
    }
}
