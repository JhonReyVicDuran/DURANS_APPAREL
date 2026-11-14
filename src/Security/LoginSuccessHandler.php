<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token
    ): ?RedirectResponse {

        $user = $token->getUser();

        // ADMIN: Redirect to Admin Dashboard
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('admin_dashboard')
            );
        }

        // STAFF: Redirect to Staff Dashboard
        if (in_array('ROLE_STAFF', $user->getRoles(), true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('staff_dashboard')
            );
        }

        // CUSTOMER: Redirect to Website Homepage
        return new RedirectResponse(
            $this->urlGenerator->generate('app_home')
        );
    }
}