<?php

namespace App\Repository;

use Carbon\Carbon;
use App\Entity\MetierAds;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<MetierAds>
 */
class MetierAdsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetierAds::class);
    }

    //    /**
    //     * @return MetierAds[] Returns an array of MetierAds objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MetierAds
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    /**
     * Find active ads with a deadline in the future
     *
     * @return MetierAds[]
     */
    public function findActiveAds(): array
{
    $now = new \DateTime();

    return $this->createQueryBuilder('a')
        ->where('a.status = :status')
        ->andWhere('a.deadline IS NULL OR a.deadline > :now')
        ->setParameter('status', 1)
        ->setParameter('now', $now)
        ->getQuery()
        ->getResult(); // ← this returns entities, not arrays
}

}
