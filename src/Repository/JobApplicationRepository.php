<?php

namespace App\Repository;

use App\Entity\User;
use DateTimeImmutable;
use App\Entity\EmployerJobs;
use Doctrine\DBAL\Connection;
use App\Entity\JobApplication;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Criteria;

/**
 * @extends ServiceEntityRepository<JobApplication>
 */
class JobApplicationRepository extends ServiceEntityRepository
{

    private $connection;

    public function __construct(ManagerRegistry $registry, Connection $connection)
    {
        parent::__construct($registry, JobApplication::class);
        $this->connection = $connection;
    }


    /**
     * @return JobApplication[] Returns an array of Project objects
     */
    public function filterApplications(
        $employer = null,
        $jobseeker = null,
        $type = null,
        $job = null
    ): array {
        $qb = $this->createQueryBuilder('p');
    
        if ($type) {
            $qb->andWhere('p.status = :type')
               ->setParameter('type', $type);
        }
    
        if ($employer) {
            $qb->andWhere('p.employer = :employer')
               ->setParameter('employer', $employer);
        }
    
        if ($jobseeker) { // Fixed this condition
            $qb->andWhere('p.jobSeeker = :jobseeker')
               ->setParameter('jobseeker', $jobseeker);
        }
        if ($job) { // Fixed this condition
            $qb->andWhere('p.job = :job')
               ->setParameter('job', $job);
        }
    
        // Add ordering by createdAt in descending order
        $qb->orderBy('p.createdAt', 'DESC');
    
        return $qb->getQuery()->getResult();
    }
    



    //    /**
    //     * @return JobApplication[] Returns an array of JobApplication objects
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

    //    public function findOneBySomeField($value): ?JobApplication
    //    {
    //        return $this->createQueryBuilder('j')
    //            ->andWhere('j.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findJobSeekerJob(User $jobSeeker, EmployerJobs $job): ?JobApplication
    {
        return $this->createQueryBuilder('j')
            ->where('j.jobSeeker = :jobSeeker')
            ->andWhere('j.job = :job')
            ->setParameter('jobSeeker', $jobSeeker)
            ->setParameter('job', $job)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getCandidateComparisonLastSixMonths(User $user): array
    {
        $sql = '
            SELECT DATE_FORMAT(created_at, "%M %Y") as month, COUNT(id) as count
            FROM job_application
            WHERE created_at >= :date AND employer_id = :userId
            GROUP BY month
            ORDER BY month ASC
        ';

        $stmt = $this->connection->executeQuery(
            $sql,
            [
                'date' => (new \DateTime('-6 months'))->format('Y-m-d'),
                'userId' => $user->getId(),
            ]
        );

        return $stmt->fetchAllAssociative();
    }

   /**
 * @return array
 */
public function findMatchingJobApplicationss(User $employer)
{
    
}



 /**
 * @param string|null $status Status of the job application, can be null
 * @param Employer $employer The employer whose job applications you want to filter
 * 
 * @return JobApplication[] Returns an array of JobApplication objects
 */
public function filter(?string $status, $employer): array
{
    $qb = $this->createQueryBuilder('a')
        ->andWhere('a.status != :deleted')
        ->setParameter('deleted', 'deleted')
        ->andWhere('a.employer = :employer')
        ->setParameter('employer', $employer);

    // If status is provided, add it to the query
    if ($status) {
        $qb->andWhere('a.status = :status')
           ->setParameter('status', $status);
    }

    // Order by latest createdAt first
    $qb->orderBy('a.createdAt', 'DESC');

    return $qb->getQuery()->getResult();
}


public function findMatchingJobApplications(User $employer)
{
    $qb = $this->createQueryBuilder('ja')
        ->select(
            'ja.id',
            'ja.document',
            'ja.status',
            'ja.cv',
            'ja.type',
            'ja.createdAt',
            'job.id AS jobId',
            'job.experience AS jobEx',
            'job.education_required AS jobEducation',
            'job.locals_only AS localsOnly',
            'user.id AS jobSeekerId',
            'user.name AS jobSeekerName',
            'resumedetails.experience AS jobSeekerExperience',
            'SUM(CASE WHEN job.country = details.country THEN 1 ELSE 0 END) AS countryMatch',
            'SUM(CASE WHEN resumedetails.education >= job.education_required THEN 1 ELSE 0 END) AS educationMatch',
            'SUM(CASE WHEN resumedetails.experience >= job.experience THEN 1 ELSE 0 END) AS experienceMatch',
            'COUNT(DISTINCT jobSkills.id) AS jobSkillCount',
            'COUNT(DISTINCT sharedSkills.id) AS sharedSkillsCount'
        )
        ->innerJoin('ja.job', 'job')
        ->leftJoin('job.required_skill', 'jobSkills')
        ->innerJoin('ja.jobSeeker', 'user')
        ->leftJoin('user.jobSeekerResume', 'resumedetails')
        ->leftJoin('user.jobseekerDetails', 'details')
        ->leftJoin('resumedetails.skills', 'resumedetailsSkills')
        ->leftJoin('job.required_skill', 'sharedSkills', 'WITH', 'sharedSkills.id = resumedetailsSkills.id')
        ->where('ja.employer = :employer')
        ->setParameter('employer', $employer)
        ->andWhere('ja.type = :type')
        ->setParameter('type', 'self')
        ->andWhere('ja.status != :rejectedStatus')
        ->setParameter('rejectedStatus', 'rejected')  // Exclude rejected job applications
        // Add this condition to exclude non-matching country when locals_only is true
        ->andWhere('(job.locals_only = false OR job.country = details.country)')
        ->groupBy('ja.id');

    $results = $qb->getQuery()->getArrayResult();

    // Add match score calculation and filter results
    $filteredResults = [];
    foreach ($results as $result) {
        $matchScore = 0;
        $matchedItems = [];

        // Check locals_only condition for country matching
        if ($result['localsOnly'] && $result['countryMatch'] > 0) {
            $matchScore += 1;
            $matchedItems[] = 'Country';
        } elseif (!$result['localsOnly']) {
            // If locals_only is false, ignore country match
            $matchScore += 1;  // Count this as a match
            $matchedItems[] = 'Country (ignored)';
        }

        if ($result['educationMatch'] > 0) {
            $matchScore += 1;
            $matchedItems[] = 'Education';
        }
        if ($result['experienceMatch'] > 0) {
            $matchScore += 1;
            $matchedItems[] = 'Experience';
        }

        // Ensure at least 3 shared skills
        if ($result['sharedSkillsCount'] >= 3) {
            $matchScore += 1;
            $matchedItems[] = 'Skills';
        }

        // Only include results where all 3 major criteria are met (education, experience, skills)
        if ($matchScore >= 3 && $result['sharedSkillsCount'] >= 3) {
            $result['matchScore'] = $matchScore;
            $result['matchScorePercentage'] = ($matchScore / 4) * 100;
            $result['matchedItems'] = $matchedItems;
            $filteredResults[] = $result;
        }
    }

    // Sort by matchScore in descending order
    usort($filteredResults, function ($a, $b) {
        return $b['matchScore'] <=> $a['matchScore'];
    });

    return $filteredResults;
}



 


 // the real working but the skills are not working
//  public function findMatchingJobApplications(User $employer)
// {
//     $qb = $this->createQueryBuilder('ja')
//         ->select(
//             'ja.id',
//             'ja.document',
//             'ja.status',
//             'ja.cv',
//             'ja.type',
//             'ja.createdAt',
//             'job.id AS jobId',
//             'job.experience AS jobEx',
//             'job.education_required AS jobEducation',
//             'user.id AS jobSeekerId',
//             'user.name AS jobSeekerName',
//             'resumedetails.experience AS jobSeekerExperience',
//             'SUM(CASE WHEN job.country = details.country THEN 1 ELSE 0 END) AS countryMatch',
//             'SUM(CASE WHEN resumedetails.education >= job.education_required THEN 1 ELSE 0 END) AS educationMatch',
//             'SUM(CASE WHEN resumedetails.experience >= job.experience THEN 1 ELSE 0 END) AS experienceMatch',
//             'COUNT(jobSkills.id) AS skillMatchCount'
//         )
//         ->innerJoin('ja.job', 'job')
//         ->leftJoin('job.required_skill', 'jobSkills')
//         ->innerJoin('ja.jobSeeker', 'user')
//         ->leftJoin('user.jobseekerDetails', 'details')
//         ->leftJoin('user.jobSeekerResume', 'resumedetails')
//         ->where('ja.employer = :employer')
//         ->setParameter('employer', $employer)
//         ->andWhere('ja.type = :type')
//         ->setParameter('type', 'self')
//         ->groupBy('ja.id')
//         ->having('
//             SUM(CASE WHEN job.country = details.country THEN 1 ELSE 0 END) +
//             SUM(CASE WHEN resumedetails.education >= job.education_required THEN 1 ELSE 0 END) +
//             SUM(CASE WHEN resumedetails.experience >= job.experience THEN 1 ELSE 0 END) +
//             COUNT(jobSkills.id) >= 2
//         ')
//         ->andHaving('COUNT(jobSkills.id) >= 2');

//     $results = $qb->getQuery()->getArrayResult();

//     // Add match score calculation and filter results
//     $filteredResults = [];
//     foreach ($results as $result) {
//         $matchScore = 0;
//         $matchedItems = [];

//         if ($result['countryMatch'] > 0) {
//             $matchScore += 1;
//             $matchedItems[] = 'Country';
//         }
//         if ($result['educationMatch'] > 0) {
//             $matchScore += 1;
//             $matchedItems[] = 'Education';
//         }
//         if ($result['experienceMatch'] > 0) {
//             $matchScore += 1;
//             $matchedItems[] = 'Experience';
//         }
//         if ($result['skillMatchCount'] >= 3) {
//             $matchScore += 1;
//             $matchedItems[] = 'Skills';
//         }

//         if ($matchScore >= 3) {
//             $result['matchScore'] = $matchScore;
//             $result['matchScorePercentage'] = ($matchScore / 4) * 100;
//             $result['matchedItems'] = $matchedItems;
//             $filteredResults[] = $result;
//         }
//     }

//     // Sort by matchScore in descending order
//     usort($filteredResults, function ($a, $b) {
//         return $b['matchScore'] <=> $a['matchScore'];
//     });

//     return $filteredResults;
// }
}
