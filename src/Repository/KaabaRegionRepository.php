<?php

namespace App\Repository;

use App\Entity\KaabaRegion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KaabaRegion>
 */
class KaabaRegionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaRegion::class);
    }

//    /**
//     * @return KaabaRegion[] Returns an array of KaabaRegion objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('k')
//            ->andWhere('k.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('k.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?KaabaRegion
//    {
//        return $this->createQueryBuilder('k')
//            ->andWhere('k.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
