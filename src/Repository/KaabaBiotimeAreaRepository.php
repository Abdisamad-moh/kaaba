<?php

namespace App\Repository;

use App\Entity\KaabaBiotimeArea;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class KaabaBiotimeAreaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaBiotimeArea::class);
    }

    public function findAreasWithInstitute()
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.institute', 'i')
            ->addSelect('i')
            ->orderBy('a.area_name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAreaByInstitute($instituteId)
    {
        return $this->createQueryBuilder('a')
            ->join('a.institute', 'i')
            ->where('i.id = :instituteId')
            ->setParameter('instituteId', $instituteId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}