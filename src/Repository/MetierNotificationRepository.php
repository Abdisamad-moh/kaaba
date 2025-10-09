<?php

namespace App\Repository;

use App\Entity\MetierNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MetierNotification>
 */
class MetierNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetierNotification::class);
    }

    //    /**
    //     * @return MetierNotification[] Returns an array of MetierNotification objects
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

    //    public function findOneBySomeField($value): ?MetierNotification
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Fetch unread notifications for a specific user
     *
     * @param int $userId
     * @param int|null $limit
     * @return MetierNotification[]
     */
    public function findUnreadNotifications(int $userId, int $limit = null): array
    {
        $queryBuilder = $this->createQueryBuilder('n')
            ->andWhere('n.user = :userId')
            ->andWhere('n.is_read = :isRead')
            ->setParameter('userId', $userId)
            ->setParameter('isRead', false)
            ->orderBy('n.id', 'DESC');
        // ->orderBy('n.createdAt', 'DESC');

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Count unread notifications for a specific user
     *
     * @param int $userId
     * @return int
     */
    public function countUnreadNotifications(int $userId): int
    {
        return $this->createQueryBuilder('n')
            ->select('count(n.id)')
            ->andWhere('n.user = :userId')
            ->andWhere('n.is_read = :isRead')
            ->setParameter('userId', $userId)
            ->setParameter('isRead', false)
            ->orderBy('n.id', 'DESC')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLatestByUser($user)
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.id', 'DESC') // Order by createdAt descending
            ->getQuery()
            ->getResult();
    }
}
