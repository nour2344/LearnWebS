<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final class ParentStudentUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private int $studentId,
        private string $loginId,
        private string $displayName,
        private ?string $passwordHash = null,   // <— add this
    ) {}

    public function getUserIdentifier(): string
    {
        // any stable unique identifier for the parent session
        return 'parent#'.$this->studentId;
    }

    public function getRoles(): array
    {
        return ['ROLE_PARENT'];
    }

    /** Needed for remember-me signature */
    public function getPassword(): ?string
    {
        return $this->passwordHash;            // <— return the stored hash
    }

    public function eraseCredentials(): void {}
}
