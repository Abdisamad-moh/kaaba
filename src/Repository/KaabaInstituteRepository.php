<?php

namespace App\Repository;

use App\Entity\KaabaInstitute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KaabaInstitute>
 */
class KaabaInstituteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaInstitute::class);
    }

//    /**
//     * @return KaabaInstitute[] Returns an array of KaabaInstitute objects
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

//    public function findOneBySomeField($value): ?KaabaInstitute
//    {
//        return $this->createQueryBuilder('k')
//            ->andWhere('k.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
