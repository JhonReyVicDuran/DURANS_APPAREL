<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        // No account-status checks before password verification.
    }

    public function checkPostAuth(
        UserInterface $user,
        ?TokenInterface $token = null
    ): void {
        // Only check accounts belonging to our User entity.
        if (!$user instanceof User) {
            return;
        }

        // Block deactivated Staff accounts.
        if (
            in_array('ROLE_STAFF', $user->getRoles(), true)
            && !$user->isActive()
        ) {
            throw new CustomUserMessageAccountStatusException(
                'Your Staff account has been deactivated. Please contact the administrator.'
            );
        }

        // Block deactivated Customer accounts.
        if (
            in_array('ROLE_USER', $user->getRoles(), true)
            && !in_array('ROLE_ADMIN', $user->getRoles(), true)
            && !$user->isActive()
        ) {
            throw new CustomUserMessageAccountStatusException(
                'Your Customer account has been deactivated. Please contact the administrator.'
            );
        }
    }
}