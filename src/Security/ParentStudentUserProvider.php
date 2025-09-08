<?php

namespace App\Security;

use App\Repository\EtudiantRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

final class ParentStudentUserProvider implements UserProviderInterface
{
    public function __construct(private EtudiantRepository $repo) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Identifier created in ParentIdAuthenticator: "student#<id>"
        if (!str_starts_with($identifier, 'student#')) {
            throw new UserNotFoundException(sprintf('Unsupported identifier "%s".', $identifier));
        }

        $id = (int) substr($identifier, 8);
        $s  = $this->repo->find($id);

        if (!$s) {
            throw new UserNotFoundException('Student not found.');
        }

        return new ParentStudentUser(
            $s->getId(),
            (string) $s->getLoginId(),
            trim(($s->getNom() ?? '') . ' ' . ($s->getPrenom() ?? '')),
            (string) $s->getLoginPasswordHash() // keep hash for remember_me signature
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof ParentStudentUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_debug_type($user)));
        }

        // Reload from DB to keep data fresh
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === ParentStudentUser::class || is_subclass_of($class, ParentStudentUser::class);
    }
}
