<?php

namespace App\Repository;
use Doctrine\DBAL\Types\Types;
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
    public function countDistinctStudents(): int
{
    return (int) $this->createQueryBuilder('p')
        ->select('COUNT(DISTINCT p.etudiant)')
        ->getQuery()
        ->getSingleScalarResult();
}

public function getTotalPayments(): float
{
    return (float) $this->createQueryBuilder('p')
        ->select('COALESCE(SUM(p.montantP), 0)')
        ->getQuery()
        ->getSingleScalarResult();
}

  public function sumAll(): float
{
    return (float) $this->createQueryBuilder('p')
        ->select('COALESCE(SUM(p.montantP), 0)')   // ← change to your real amount field
        ->getQuery()
        ->getSingleScalarResult();
}

public function sumBetween(\DateTimeInterface $start, \DateTimeInterface $end): float
{
    return (float) $this->createQueryBuilder('p')
        ->select('COALESCE(SUM(p.montantP), 0)')   // ← amount field
        ->andWhere('p.dateP >= :start')            // ← date field
        ->andWhere('p.dateP < :end')
        ->setParameter('start', $start, Types::DATETIME_MUTABLE)
        ->setParameter('end', $end, Types::DATETIME_MUTABLE)
        ->getQuery()
        ->getSingleScalarResult();
}

public function countDistinctStudentsPaid(): int
{
    return (int) $this->createQueryBuilder('p')
        ->select('COUNT(DISTINCT p.etudiant)')            // ← relation name to Etudiant
        ->getQuery()
        ->getSingleScalarResult();
}
}

