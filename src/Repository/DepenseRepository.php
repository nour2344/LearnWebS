<?php

namespace App\Repository;
use App\Entity\Depense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Types;
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


 public function searchQuery(?string $categorie)
    {
        $qb = $this->createQueryBuilder('d')
            ->orderBy('d.dateD', 'DESC');

        if ($categorie !== null && $categorie !== '') {
            $qb->andWhere('d.categorie = :cat')->setParameter('cat', $categorie);
        }

        return $qb->getQuery();
 
    }

    //D
    public function sumAll(): float
    {
        return (float) $this->createQueryBuilder('d')
            ->select('COALESCE(SUM(d.montantD), 0)') // ⬅ change to d.montant if needed
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumBetween(\DateTimeInterface $start, \DateTimeInterface $end): float
{
    return (float) $this->createQueryBuilder('d')
        ->select('COALESCE(SUM(d.montantD), 0)') // adjust if your amount field differs
        ->andWhere('d.dateD >= :start')
        ->andWhere('d.dateD < :end')
        ->setParameter('start', $start, Types::DATETIME_MUTABLE) // or DATETIME_IMMUTABLE if applicable
        ->setParameter('end', $end, Types::DATETIME_MUTABLE)
        ->getQuery()
        ->getSingleScalarResult();
}
    /** Returns [ ['categorie' => 'X', 'total' => 123.0], ... ] */
    public function sumByCategoryTop(int $limit = 5): array
{
    $rows = $this->createQueryBuilder('d')
        ->select('d.categorie AS categorie, COALESCE(SUM(d.montantD), 0) AS total') // adjust montant field if needed
        ->groupBy('d.categorie')
        ->orderBy('total', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getArrayResult();

    foreach ($rows as &$r) {
        $cat = $r['categorie'];
        if ($cat instanceof \BackedEnum) {
            $r['categorie'] = $cat->value;
        } elseif ($cat instanceof \UnitEnum) {
            $r['categorie'] = $cat->name;
        } else {
            $r['categorie'] = (string) $cat; // fallback if custom type
        }
        $r['total'] = (float) $r['total'];
    }
    unset($r);

    return $rows;
}

}
