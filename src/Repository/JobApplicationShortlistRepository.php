<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\JobApplicationShortlist;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<JobApplicationShortlist>
 */
class JobApplicationShortlistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobApplicationShortlist::class);
    }

//    /**
//     * @return JobApplicationShortlist[] Returns an array of JobApplicationShortlist objects
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

//    public function findOneBySomeField($value): ?JobApplicationShortlist
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findEmployerShortlist(User $user, $job = null, $score = null)
    {
        $data = $this->createQueryBuilder('s')
            ->join('s.application', 'a')
            ->join('a.job', 'j')
            ->where('j.employer = :employer')
            ->setParameter('employer', $user);
        if ($score) {
            $data->andWhere('s.score >= :score')
            ->setParameter('score', $score);
        }

        if ($job) {
            $data->andWhere('j = :job')
            ->setParameter('job', $job);
        }

        

        return $data->getQuery()->getResult();
    }
}
