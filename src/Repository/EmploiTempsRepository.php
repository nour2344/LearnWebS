<?php

namespace App\Repository;

use App\Entity\EmploiTemps;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EmploiTempsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmploiTemps::class);
    }

    /** Latest timetable for a class as of a date (default: today) */
   public function findLatestForClass(string $classe, bool $includeFuture = false): ?EmploiTemps
{
    $qb = $this->createQueryBuilder('e')
        ->andWhere('LOWER(e.classe) = LOWER(:classe)')
        ->setParameter('classe', $classe)
        ->orderBy('e.effectiveFrom', 'DESC')
        ->addOrderBy('e.id', 'DESC');

    if (!$includeFuture) {
        $qb->andWhere('e.effectiveFrom <= :today')
           ->setParameter('today', new \DateTimeImmutable('today'));
    }

    return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
}
}
