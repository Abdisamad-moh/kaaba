<?php

namespace App\Repository;

use App\Entity\KaabaApplicationDuplicate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KaabaApplicationDuplicate>
 */
class KaabaApplicationDuplicateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaApplicationDuplicate::class);
    }
}