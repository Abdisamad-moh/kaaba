<?php

namespace App\Repository;

use App\Entity\MetierCareers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Careers>
 *
 * @method Careers|null find($id, $lockMode = null, $lockVersion = null)
 * @method Careers|null findOneBy(array $criteria, array $orderBy = null)
 * @method Careers[]    findAll()
 * @method Careers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CareersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetierCareers::class);
    }

    //    /**
    //     * @return Careers[] Returns an array of Careers objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Careers
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
