<?php

namespace App\Repository;

use App\Entity\Depense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Depense>
 */
class DepenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Depense::class);
    }

//    /**
//     * @return Depense[] Returns an array of Depense objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Depense
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


public function sumByDate(\DateTimeInterface $date): float
{
    $start = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
    $end = \DateTimeImmutable::createFromInterface($date)->setTime(23, 59, 59);

    return (float) $this->createQueryBuilder('d')
        ->select('SUM(d.montantD)')
        ->where('d.dateD BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getSingleScalarResult();
}


public function sumFromDate(\DateTimeInterface $startDate): float
{
    return (float) $this->createQueryBuilder('d')
        ->select('SUM(d.montantD)')
        ->where('d.dateD >= :startDate')
        ->setParameter('startDate', $startDate)
        ->getQuery()
        ->getSingleScalarResult();
}

public function sumAll(): float
{
    return (float) $this->createQueryBuilder('d')
        ->select('SUM(d.montantD)')
        ->getQuery()
        ->getSingleScalarResult();
}
 public function searchQuery(?string $categorie)
    {
        $qb = $this->createQueryBuilder('d')
            ->orderBy('d.dateD', 'DESC');

        if ($categorie !== null && $categorie !== '') {
            $qb->andWhere('d.categorie = :cat')->setParameter('cat', $categorie);
        }

        return $qb->getQuery();
    }
}
