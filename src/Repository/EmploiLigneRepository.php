<?php
namespace App\Repository;

use App\Entity\EmploiLigne;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmploiLigne>
 */
class EmploiLigneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmploiLigne::class);
    }

    // add your custom queries here if needed
}
