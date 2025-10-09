<?php

namespace App\Repository;

use App\Entity\JobSeekerRecommendedJobs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobSeekerRecommendedJobs>
 */
class JobSeekerRecommendedJobsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobSeekerRecommendedJobs::class);
    }

    //    /**
    //     * @return JobSeekerRecommendedJobs[] Returns an array of JobSeekerRecommendedJobs objects
    //     */
    public function filter($employer): array
    {
        $data = $this->createQueryBuilder('a');
        $data->andWhere('a.employer =  :emp')->setParameter('emp', $employer);
      
        $data = $data->getQuery()->getResult();

        return $data;
    }
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('j')
    //            ->andWhere('j.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('j.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?JobSeekerRecommendedJobs
    //    {
    //        return $this->createQueryBuilder('j')
    //            ->andWhere('j.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
