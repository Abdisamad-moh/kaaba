<?php

namespace App\Repository;

use App\Entity\JobSeekerJobAlert;
use App\Entity\MetierJobCategory;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<JobSeekerJobAlert>
 */
class JobSeekerJobAlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobSeekerJobAlert::class);
    }

//    /**
//     * @return JobSeekerJobAlert[] Returns an array of JobSeekerJobAlert objects
//     */
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

//    public function findOneBySomeField($value): ?JobSeekerJobAlert
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
/**
     * Find job seekers who have at least one matching job category
     * 
     * @param MetierJobCategory $jobCategory
     * @return JobSeekerJobAlert[]
     */
    public function findJobSeekersByMatchingJobCategory(MetierJobCategory $jobCategory): array
    {
        return $this->createQueryBuilder('alert')
            ->andWhere(':jobCategory MEMBER OF alert.jobcategory')
            ->setParameter('jobCategory', $jobCategory)
            ->getQuery()
            ->getResult();
    }
    
}
