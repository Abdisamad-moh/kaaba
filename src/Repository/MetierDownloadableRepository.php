<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\MetierDownloadable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<MetierDownloadable>
 */
class MetierDownloadableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetierDownloadable::class);
    }

    //    /**
    //     * @return MetierDownloadable[] Returns an array of MetierDownloadable objects
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

    //    public function findOneBySomeField($value): ?MetierDownloadable
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    /**
 * Check if the current user has downloadable files of a specific type that haven't expired or been downloaded.
 *
 * @param \App\Entity\User $user  The current user.
 * @param string $category        The category/type of downloadable.
 * @return MetierDownloadable|null  The MetierDownloadable entity if found, or null if none are available.
 */
public function getValidDownloadable(User $user, string $category): ?MetierDownloadable
{
    $qb = $this->createQueryBuilder('d')
        ->where('d.user = :user')
        ->andWhere('d.type = :type')
        ->andWhere('d.has_downloaded = false')
        ->andWhere('d.expiration_date > :now')
        ->setParameter('user', $user)
        ->setParameter('type', $category)
        ->setParameter('now', new \DateTime('now'))
        ->setMaxResults(1); // Only one valid downloadable is needed

    try {
        $downloadable = $qb->getQuery()->getOneOrNullResult();
    } catch (NonUniqueResultException $e) {
        return null;
    }

    return $downloadable;
}
}
