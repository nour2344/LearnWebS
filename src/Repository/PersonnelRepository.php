<?php

namespace App\Repository;

use App\Entity\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PersonnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Personnel::class);
    }

    /**
     * Build a query filtered by:
     * - $q: matches nom or prénom (case-insensitive)
     * - $role: matches enum value string (e.g. "Professeur")
     */
    public function searchQuery(?string $q, ?string $role)
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.nomP', 'ASC');

        if ($q) {
            $qb->andWhere('p.nomP LIKE :q OR p.prenomP LIKE :q')
               ->setParameter('q', "%$q%");
        }

        if ($role) {
            // role is a backed string enum stored as string → direct equality
            $qb->andWhere('p.role = :role')
               ->setParameter('role', $role);
        }

        return $qb->getQuery();
    }
}
