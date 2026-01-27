<?php

namespace App\Repository;

use App\Entity\KaabaBiotimeDevice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class KaabaBiotimeDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaBiotimeDevice::class);
    }

    public function findActiveDevices()
    {
        return $this->createQueryBuilder('d')
            ->where('d.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('d.device_name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findDevicesByArea($areaId)
    {
        return $this->createQueryBuilder('d')
            ->join('d.area', 'a')
            ->where('a.id = :areaId')
            ->setParameter('areaId', $areaId)
            ->orderBy('d.device_name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}