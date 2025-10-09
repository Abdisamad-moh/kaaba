<?php

namespace App\Repository;

use App\Entity\MetierPackages;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<MetierPackages>
 */
class MetierPackagesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetierPackages::class);
    }

    //    /**
    //     * @return MetierPackages[] Returns an array of MetierPackages objects
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

    //    public function findOneBySomeField($value): ?MetierPackages
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findByType(string $type = null)
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.name', 'ASC');

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        return $qb;
    }

    public function paginatePackagesPerTypeAndQuery(int $offset, int $num_rows = 16, bool $single_scaler = false, string $type = null, string $query = null)
    {
        $qb = $this->createQueryBuilder('p');

        if ($type) {
            $qb->andWhere('p.class = :class')
               ->setParameter('class', $type);
        }

        if($query)
        {
            $qb->andWhere('p.name LIKE :query')
               ->setParameter('query', '%'.$query.'%');
        }

        $qb->setMaxResults($num_rows)
           ->setFirstResult($offset)
           ->orderBy('p.name', 'ASC');

        $query = $qb->getQuery()->getResult();

        return $query;
    }
}
