<?php

namespace App\Repository;

use App\Entity\KaabaConfigSchoolHoliday;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class KaabaConfigSchoolHolidayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaConfigSchoolHoliday::class);
    }

    public function findAllSorted()
    {
        return $this->createQueryBuilder('sh')
            ->orderBy('sh.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}