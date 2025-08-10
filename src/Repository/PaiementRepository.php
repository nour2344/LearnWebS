<?php

namespace App\Repository;

use App\Entity\Paiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

    /**
     * Builds a filtered query for Paiement with optional search and filters.
     */
    public function searchQuery(?string $q, ?string $type, ?string $from, ?string $to, ?string $min, ?string $max)
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.etudiant', 'e')->addSelect('e')
            ->orderBy('p.dateP', 'DESC');

        if ($q) {
            $qb->andWhere('e.nom LIKE :q OR e.prenom LIKE :q OR p.note LIKE :q')
               ->setParameter('q', "%$q%");
        }

        if ($type) {
            $qb->andWhere('p.typePaiement = :type')->setParameter('type', $type);
        }

        if ($from) {
            $qb->andWhere('p.dateP >= :from')
               ->setParameter('from', new \DateTime($from . ' 00:00:00'));
        }
        if ($to) {
            $qb->andWhere('p.dateP <= :to')
               ->setParameter('to', new \DateTime($to . ' 23:59:59'));
        }

        if (is_numeric($min)) {
            $qb->andWhere('p.montantP >= :min')->setParameter('min', (float) $min);
        }
        if (is_numeric($max)) {
            $qb->andWhere('p.montantP <= :max')->setParameter('max', (float) $max);
        }

        return $qb->getQuery();
    }
}
