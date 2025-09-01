<?php
namespace App\Repository;

use App\Entity\ChatLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChatLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatLog::class);
    }

    /** @return ChatLog[] */
    public function lastForUser(int $userId, int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :u')->setParameter('u', $userId)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }
}
