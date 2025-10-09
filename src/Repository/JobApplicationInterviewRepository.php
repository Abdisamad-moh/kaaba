<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\JobApplicationInterview;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<JobApplicationInterview>
 */
class JobApplicationInterviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobApplicationInterview::class);
    }

    //    /**
    //     * @return JobApplicationInterview[] Returns an array of JobApplicationInterview objects
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

    //    public function findOneBySomeField($value): ?JobApplicationInterview
    //    {
    //        return $this->createQueryBuilder('j')
    //            ->andWhere('j.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
        //    /**
    //     * @return JobApplicationInterview[] Returns an array of JobApplicationInterview objects
    //     */
       public function allInterviews($employer): array
       {
           return $this->createQueryBuilder('j')
            ->andWhere('j.status !=  :status')->setParameter('status', "hired")
               ->andWhere('j.employer = :employer')
               ->setParameter('employer', $employer)
               ->orderBy('j.id', 'ASC')
               ->getQuery()
               ->getResult()
           ;
       }

       public function findByFilters(User $user, ?int $jobId, ?string $fromDate, ?string $toDate, ?string $status): array
{
    $qb = $this->createQueryBuilder('i')
        ->leftJoin('i.application', 'a')
        ->leftJoin('a.job', 'j')
        ->where('i.employer = :user')
        ->setParameter('user', $user);

    if ($jobId) {
        $qb->andWhere('j.id = :jobId')
            ->setParameter('jobId', $jobId);
    }

    if ($fromDate) {
        $qb->andWhere('i.date >= :fromDate')
            ->setParameter('fromDate', new \DateTime($fromDate));
    }

    if ($toDate) {
        $qb->andWhere('i.date <= :toDate')
            ->setParameter('toDate', new \DateTime($toDate));
    }

    if ($status) {
        $qb->andWhere('i.status = :status')
            ->setParameter('status', $status);
    }

    return $qb->getQuery()->getResult();
}
}
