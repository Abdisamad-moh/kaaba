<?php

namespace App\Repository;

use App\Entity\KaabaSmsLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KaabaSmsLog>
 */
class KaabaSmsLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaSmsLog::class);
    }

    /**
     * Get logs by phone number
     */
    public function findByPhone(string $phone)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.phoneNumber = :phone')
            ->setParameter('phone', $phone)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get logs for a specific application
     */
    public function findByApplication(int $appId)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.application = :id')
            ->setParameter('id', $appId)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get most recent logs (for dashboard)
     */
    public function findLatest(int $limit = 20)
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
