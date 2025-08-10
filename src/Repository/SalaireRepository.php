<?php

namespace App\Repository;

use App\Entity\Salaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SalaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Salaire::class);
    }


public function qbAllWithPersonnel()
{
    return $this->createQueryBuilder('s')
        ->leftJoin('s.personnel', 'p')->addSelect('p')
        ->orderBy('s.dateP', 'DESC');
}

public function findDueWithinDays(int $days = 7): array
{
    $now   = new \DateTimeImmutable('today');
    $limit = $now->modify("+$days days");

    return $this->createQueryBuilder('s')
        ->andWhere('s.estPaye = false')
        ->andWhere('s.dateP BETWEEN :now AND :limit')
        ->setParameter('now', $now)
        ->setParameter('limit', $limit)
        ->orderBy('s.dateP', 'ASC')
        ->getQuery()->getResult();
}

public function findOverdue(): array
{
    $now = new \DateTimeImmutable('today');

    return $this->createQueryBuilder('s')
        ->andWhere('s.estPaye = false')
        ->andWhere('s.dateP < :now')
        ->setParameter('now', $now)
        ->orderBy('s.dateP', 'ASC')
        ->getQuery()->getResult();
}


public function searchQuery(?string $mois, ?string $personnel, ?string $statut)
{
    $qb = $this->createQueryBuilder('s')
        ->leftJoin('s.personnel', 'p')->addSelect('p')
        ->orderBy('s.dateP', 'DESC');

    if ($mois) {
        $qb->andWhere('s.mois LIKE :mois')->setParameter('mois', "%$mois%");
    }

    if ($personnel) {
        $qb->andWhere('p.nomP LIKE :pers OR p.prenomP LIKE :pers')
           ->setParameter('pers', "%$personnel%");
    }

    if ($statut !== null && $statut !== '') {
        // expected values: "paye", "non"
        $isPaid = $statut === 'paye';
        $qb->andWhere('s.estPaye = :paid')->setParameter('paid', $isPaid);
    }

    return $qb->getQuery();
}
public function findLatePayments(): array
{
    $qb = $this->createQueryBuilder('s')
        ->where('s.statut = :statut')
        ->setParameter('statut', 'Non payé');
    return $qb->getQuery()->getResult();
}

}
