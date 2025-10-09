<?php

namespace App\Repository;

use App\Entity\KaabaQualification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KaabaQualification>
 */
class KaabaQualificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaQualification::class);
    }

//    /**
//     * @return KaabaQualification[] Returns an array of KaabaQualification objects
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

//    public function findOneBySomeField($value): ?KaabaQualification
//    {
//        return $this->createQueryBuilder('k')
//            ->andWhere('k.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
