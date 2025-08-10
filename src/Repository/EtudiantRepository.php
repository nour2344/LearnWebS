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


}
