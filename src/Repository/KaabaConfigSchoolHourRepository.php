<?php

namespace App\Repository;

use App\Entity\KaabaConfigSchoolHour;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class KaabaConfigSchoolHourRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaConfigSchoolHour::class);
    }

    public function findAllWithInstitute()
    {
        return $this->createQueryBuilder('sh')
            ->leftJoin('sh.institute', 'i')
            ->addSelect('i')
            ->orderBy('i.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}