<?php

namespace App\Repository;

use App\Entity\KaabaAssessmentLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KaabaAssessmentLog>
 */
class KaabaAssessmentLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaAssessmentLog::class);
    }

   
}