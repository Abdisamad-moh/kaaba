<?php
// src/Repository/KaabaApplicationLogRepository.php

namespace App\Repository;

use App\Entity\KaabaApplicationLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KaabaApplicationLog>
 */
class KaabaApplicationLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaApplicationLog::class);
    }
}