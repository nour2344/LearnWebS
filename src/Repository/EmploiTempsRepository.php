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
public function searchManual(?string $term): array
{
    $qb = $this->createQueryBuilder('e')
        ->andWhere('e.mode = :m')->setParameter('m', 'manual')
        ->orderBy('e.classe', 'ASC')
        ->addOrderBy('e.effectiveFrom', 'DESC');

    $term = trim((string) $term);
    if ($term !== '') {
        $qb->andWhere('e.classe LIKE :t OR e.titre LIKE :t')
           ->setParameter('t', '%'.$term.'%');
    }

    return $qb->getQuery()->getResult();
}

}
