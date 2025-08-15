<?php

namespace App\Repository;
use Doctrine\DBAL\Types\Types;

use App\Entity\Recette;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recette>
 */
class RecetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recette::class);
    }

//    /**
//     * @return Recette[] Returns an array of Recette objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Recette
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


public function sumByDate(\DateTimeInterface $date): float
{
    $start = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
    $end = \DateTimeImmutable::createFromInterface($date)->setTime(23, 59, 59);

    return (float) $this->createQueryBuilder('r')
        ->select('SUM(r.montantR)')
        ->where('r.dateR BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getSingleScalarResult();
}



public function sumFromDate(\DateTimeInterface $startDate): float
{
    return (float) $this->createQueryBuilder('r')
        ->select('SUM(r.montantR)')
        ->where('r.dateR >= :startDate')
        ->setParameter('startDate', $startDate)
        ->getQuery()
        ->getSingleScalarResult();
}



public function searchQuery(?string $source)
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.dateR', 'DESC');

        if ($source !== null && $source !== '') {
            // enum is stored as string in DB: exact match on backing value
            $qb->andWhere('r.source = :src')->setParameter('src', $source);
        }

        return $qb->getQuery();
    }

    //D
     public function sumAll(): float
    {
        return (float) $this->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.montantR), 0)')
            ->getQuery()
            ->getSingleScalarResult();
    }

   
public function sumBetween(\DateTimeInterface $start, \DateTimeInterface $end): float
{
    return (float) $this->createQueryBuilder('r')
        ->select('COALESCE(SUM(r.montantR), 0)')
        ->andWhere('r.dateR >= :start')
        ->andWhere('r.dateR < :end')
        ->setParameter('start', $start, Types::DATETIME_MUTABLE) // or DATETIME_IMMUTABLE if applicable
        ->setParameter('end', $end, Types::DATETIME_MUTABLE)
        ->getQuery()
        ->getSingleScalarResult();
}
}
