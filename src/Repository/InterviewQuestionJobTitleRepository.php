<?php

namespace App\Repository;

use App\Entity\InterviewQuestionJobTitle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InterviewQuestionJobTitle>
 */
class InterviewQuestionJobTitleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InterviewQuestionJobTitle::class);
    }

    //    /**
    //     * @return InterviewQuestionJobTitle[] Returns an array of InterviewQuestionJobTitle objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('i.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?InterviewQuestionJobTitle
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function searchByQuery(string $query): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(50) // Limit to 10 results
            ->getQuery()
            ->getResult();
    }
}
