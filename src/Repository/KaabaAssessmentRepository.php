<?php

namespace App\Repository;

use App\Entity\KaabaAssessment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KaabaAssessment>
 */
class KaabaAssessmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaAssessment::class);
    }

    public function save(KaabaAssessment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(KaabaAssessment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByApplicationId(int $applicationId): ?KaabaAssessment
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.application', 'app')
            ->andWhere('app.id = :applicationId')
            ->setParameter('applicationId', $applicationId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByTotalScoreRange(?int $minScore = null, ?int $maxScore = null): array
    {
        $qb = $this->createQueryBuilder('a');

        if ($minScore !== null) {
            $qb->andWhere('a.totalScore >= :minScore')
               ->setParameter('minScore', $minScore);
        }

        if ($maxScore !== null) {
            $qb->andWhere('a.totalScore <= :maxScore')
               ->setParameter('maxScore', $maxScore);
        }

        return $qb->orderBy('a.totalScore', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function findByRecommendedStatus(string $status): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.recommendedStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('a.totalScore', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAssessmentsWithApplications(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.application', 'app')
            ->addSelect('app')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAssessmentStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT 
                COUNT(*) as total_assessments,
                AVG(total_score) as average_score,
                MIN(total_score) as min_score,
                MAX(total_score) as max_score,
                recommended_status,
                COUNT(*) as status_count
            FROM kaaba_assessments 
            GROUP BY recommended_status
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    public function findRecentAssessments(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.application', 'app')
            ->addSelect('app')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findAssessmentsByScoreThreshold(int $threshold, string $operator = '>'): array
    {
        $qb = $this->createQueryBuilder('a');

        if ($operator === '>') {
            $qb->andWhere('a.totalScore > :threshold');
        } elseif ($operator === '>=') {
            $qb->andWhere('a.totalScore >= :threshold');
        } elseif ($operator === '<') {
            $qb->andWhere('a.totalScore < :threshold');
        } elseif ($operator === '<=') {
            $qb->andWhere('a.totalScore <= :threshold');
        } else {
            $qb->andWhere('a.totalScore = :threshold');
        }

        return $qb->setParameter('threshold', $threshold)
                  ->orderBy('a.totalScore', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find assessments that haven't been updated in the last X days
     */
    public function findStaleAssessments(int $days = 30): array
    {
        $dateThreshold = new \DateTime("-$days days");

        return $this->createQueryBuilder('a')
            ->andWhere('a.updatedAt < :threshold')
            ->setParameter('threshold', $dateThreshold)
            ->getQuery()
            ->getResult();
    }
}