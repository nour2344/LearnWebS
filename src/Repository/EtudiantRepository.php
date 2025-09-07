<?php

namespace App\Repository;

use App\Entity\Etudiant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Etudiant>
 */
class EtudiantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Etudiant::class);
    }

//    /**
//     * @return Etudiant[] Returns an array of Etudiant objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Etudiant
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


public function searchByCriteria(?string $nom, ?string $prenom, ?string $classe): array
{
    $qb = $this->createQueryBuilder('e');

    if ($nom) {
        $qb->andWhere('e.nom LIKE :nom')
           ->setParameter('nom', "%$nom%");
    }
    if ($prenom) {
        $qb->andWhere('e.prenom LIKE :prenom')
           ->setParameter('prenom', "%$prenom%");
    }
    if ($classe) {
        $qb->andWhere('e.classe LIKE :classe')
           ->setParameter('classe', "%$classe%");
    }

    return $qb->getQuery()->getResult();
}
//D
 public function countAll(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

public function getDistinctClasses(): array
{
    $rows = $this->createQueryBuilder('e')
        ->select('DISTINCT e.classe AS c')
        ->where('e.classe IS NOT NULL AND e.classe <> \'\'')
        ->orderBy('e.classe', 'ASC')
        ->getQuery()->getScalarResult();

    // return flat array of strings
    return array_map(static fn($r) => (string)$r['c'], $rows);
}

/**
 * @return Etudiant[]
 */
public function suggestByClasseAndName(string $classe, string $q, int $limit = 12): array
{
    $qb = $this->createQueryBuilder('e')
        ->andWhere('e.classe = :classe')
        ->andWhere('(e.nom LIKE :q OR e.prenom LIKE :q)')
        ->setParameter('classe', $classe)
        ->setParameter('q', '%'.$q.'%')
        ->orderBy('e.nom', 'ASC')
        ->addOrderBy('e.prenom', 'ASC')
        ->setMaxResults($limit);

    return $qb->getQuery()->getResult();
}


}
