<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\MetierCity;
use App\Entity\EmployerJobs;
use App\Entity\JobApplication;
use App\Entity\JobSeekerResume;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<EmployerJobs>
 */
class EmployerJobsRepository extends ServiceEntityRepository
{
    public const JOBS_PER_PAGE = 9;

    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $em)
    {
        parent::__construct($registry, EmployerJobs::class);
    }

    //    /**
    //     * @return EmployerJobs[] Returns an array of EmployerJobs objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * @return EmployerJobs[] Returns an array of AcademySemesterOffering objects
     */
    public function filter($employer, $status): array
    {
        $data = $this->createQueryBuilder('a');
        $data->where('a.employer =  :emp')->setParameter('emp', $employer);
        $data->andWhere('a.status !=  :id')->setParameter('id', "deleted");

        if ($status) {
            $data->andWhere('a.status =  :status')->setParameter('status', $status);
        }

        $data->orderBy('a.createdAt', 'DESC');

        $data = $data->getQuery()->getResult();

        return $data;
    }

    /**
 * @return EmployerJobs[] Returns an array of EmployerJobs objects
 */
public function totalPosted($employer): array
{
    // Define the statuses to exclude
    $statuses = ['draft', 'deleted'];

    // Create the query builder
    $data = $this->createQueryBuilder('a')
        ->where('a.employer = :emp')
        ->andWhere('a.status NOT IN (:statuses)')  // Exclude the specified statuses
        ->setParameter('emp', $employer)
        ->setParameter('statuses', $statuses)  // Set the statuses parameter
        ->getQuery()
        ->getResult();

    return $data;
}

    //    public function findOneBySomeField($value): ?EmployerJobs
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function getJobPaginator(int $offset, int $num_rows = 10, $single_scaler = false, $job_title = null, $job_category_id = null, $country_id = null): Paginator
    {
        $now = new \DateTime();
        $queryBuilder = $this->createQueryBuilder('j');

        // Base query
        $queryBuilder->where('j.status = :status')
            ->setParameter('status', 'posted')
            ->andWhere('j.application_closing_date >= :dateNow') // Filter based on expiry
            ->setParameter('dateNow', $now);

        // Filtering by job title using LIKE
        if ($job_title) {
            $queryBuilder->andWhere('j.title LIKE :job_title')
                ->setParameter('job_title', '%' . $job_title . '%');
        }


        // Filtering by job category (relationship)
        if ($job_category_id) {
            dd($job_category_id);
            // $queryBuilder->leftJoin('j.job_category', 'c') // Ensure 'job_category' is the correct relationship field
            //     ->andWhere('c.id = :job_category_id')
            //     ->setParameter('job_category_id', $job_category_id);
        }

        // Filtering by country (relationship)
        if ($country_id != null) {

            $queryBuilder->leftJoin('j.country', 'ct') // Assuming 'country' is the relationship field
                ->andWhere('ct.id = :country_id')
                ->setParameter('country_id', $country_id);
        }

        if ($single_scaler) {
            $queryBuilder->select('count(j.id)');
        } else {
            $queryBuilder->setMaxResults($num_rows)
                ->setFirstResult($offset)
                ->orderBy('j.id', 'DESC');
        }

        $query = $queryBuilder->getQuery();

        return new Paginator($query);
    }
  /**
     * Save an EmployerJobs entity.
     *
     * @param EmployerJobs $employerJobs
     * @param bool $flush Whether to flush the changes to the database immediately.
     */
    public function save(EmployerJobs $employerJobs, bool $flush = true): void
    {
        $this->getEntityManager()->persist($employerJobs);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

      /**
     * @return EmployerJobs[] Returns an array of Project objects
     */
    public function filterAccounts(
        $employer = null,
        $status = null,
        $email = null,
        $verification = null
    ): array {

          // Normalize to ensure full-day coverage
        //   $startOfDay = Carbon::instance($from_date)->startOfDay()->toDateTime();
        //   $endOfDay = Carbon::instance($to_date)->endOfDay()->toDateTime();
        $qb = $this->createQueryBuilder('p');
        // Ensure we exclude deleted projects
        // $qb->where('p.is_deleted = :is_deleted')
        //     ->setParameter('is_deleted', false);

        // Apply filters as needed
        if ($employer) {
            $qb->andWhere('p.employer = :employer')
                ->setParameter('employer', $employer);
        }
        if ($status) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }
        // if ($email) {
        //         $qb->andWhere('p.email = :email')
        //         ->setParameter('email', $email);
        // }
        // if ($verification) {
        //         $qb->andWhere('p.isVerified = :verification')
        //         ->setParameter('verification', $verification);
        // }
       
        // Add ordering by createdAt in descending order
        // $qb->orderBy('p.createdAt', 'DESC');

        // Debugging: Output SQL for verification
        $query = $qb->getQuery();
        // dd($query->getSQL(), $query->getParameters());

        return $query->getResult();
    }

    public function filterA(User $employer, ?string $type = null): array
    {
        $qb = $this->createQueryBuilder('j')
            ->where('j.employer = :employer')
            ->setParameter('employer', $employer)
            ->orderBy('j.application_closing_date', 'DESC'); // Sort jobs by closing date (newest first)

        if ($type) {
            $qb->andWhere('j.status = :type')
               ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find By Filter
     */
    public function findByFilters($excludeJobId = null,$city = null, $title = null, $salary = null, $education = null, $posted_date = null, $immediate_hiring = null, $country = null, $category = null, $job_type = null, $experience = null, $offset = 0, $limit = 30)
    {
        $now = new \DateTime();
        $monthAgo = (clone $now)->sub(new \DateInterval('P1M'));
        
        $qb = $this->createQueryBuilder('j');
        $qb->where('j.status = :status')
            ->setParameter('status', 'posted')
            ->leftJoin('j.city', 'city')->leftJoin('j.country', 'ct')
            ->andWhere('j.is_private IS NULL OR j.is_private = false');
            
        // TODO Ask about statuses;
        
        if($posted_date)
        {
            $qb->andWhere('j.createdAt >= :postedDate')
                ->setParameter('postedDate', new \DateTime($posted_date));
        }
        if($excludeJobId)
        {
            $qb->andWhere('j.id != :excludeJobId')
                ->setParameter('excludeJobId', $excludeJobId);
        }

        $qb->leftJoin('j.jobtitle', 'jtitle'); // Assume you need this join

        if ($title) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('j.title', ':title'),
                    $qb->expr()->like('jtitle.name', ':title')
                )
            )->setParameter('title', '%' . $title . '%');
        }
        
        if ($job_type)
            $qb->leftJoin('j.jobTypes', 'jt')
                ->andWhere('jt.id IN (:jobTypeId)')
                ->setParameter('jobTypeId', $job_type);

        if ($country)
            $qb
        // ->leftJoin('j.country', 'ct')
                ->andWhere('ct.id = :countryId')
                ->setParameter('countryId', $country);

        if ($category)
            $qb->leftJoin('j.job_category', 'c')
                ->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $category);

        if ($city)
            $qb
        // ->leftJoin('j.city', 'city')
            ->andWhere('city.id = :cityId')
            ->setParameter('cityId', $city);

        if($salary)
        {
            [$min, $max] = explode('-', $salary);
            
            if ($max) {  // max is not empty or zero
                $qb->andWhere('j.maximum_pay >= :minSalary AND j.maximum_pay <= :maxSalary')
                    ->setParameter('minSalary', $min)
                    ->setParameter('maxSalary', $max);
            } else {  // Handle "Over" case
                $qb->andWhere('j.maximum_pay > :minSalary')
                   ->setParameter('minSalary', $min);
            }
        }

        if ($experience)
            $qb->andWhere('j.experience = :experience')
                ->setParameter('experience', $experience);

        if ($immediate_hiring == 'true')
            $qb->andWhere('j.immediate_hiring = 1');

        if ($education != '')
            $qb->andWhere('j.education_required = :education')
                ->setParameter('education', $education);
        // dd($limit, $offset);
        $qb->orderBy('j.id', 'DESC');
        // Clone the query builder to get a count of all results without pagination
        $countQb = clone $qb;
        $count = (int) $countQb->select('COUNT(j.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Apply pagination to the original query
        $jobs = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        
        return [
            'jobs' => $jobs,
            'total' => $count,
        ];
    }


    public function findMatchingJobSeekers(EmployerJobs $job)
    {
        $qb = $this->createQueryBuilder('job'); // 'jobApp' is the alias for JobApplication
    
        $qb
            ->select(
                'user.id AS jobSeekerId',
                'user.name AS jobSeekerName',
                'details.experience AS jobSeekerExperience',
                'SUM(CASE WHEN job.country = details.country THEN 1 ELSE 0 END) AS countryMatch',
                'SUM(CASE WHEN job.education_required = resumedetails.education THEN 1 ELSE 0 END) AS educationMatch',
                'SUM(CASE WHEN job.experience = resumedetails.experience THEN 1 ELSE 0 END) AS experienceMatch',
                'SUM(CASE WHEN jobSkills MEMBER OF resumedetails.skills THEN 1 ELSE 0 END) AS skillMatchCount'
            )
            
            ->leftJoin('user.jobseekerDetails', 'details') // Join User to JobseekerDetails
            ->leftJoin('user.jobSeekerResume', 'resumedetails') // Join User to ResumeDetails
            ->leftJoin('job.required_skill', 'jobSkills') // Join Job to required skills
            ->where('jobApp.job = :job') // Filtering by the specific job
            ->setParameter('job', $job)
            ->groupBy('user.id')
            ->having('SUM(CASE WHEN job.country = details.country THEN 1 ELSE 0 END) + 
                  SUM(CASE WHEN job.education_required = resumedetails.education THEN 1 ELSE 0 END) + 
                  SUM(CASE WHEN job.experience = resumedetails.experience THEN 1 ELSE 0 END) + 
                  SUM(CASE WHEN jobSkills MEMBER OF resumedetails.skills THEN 1 ELSE 0 END) >= 3')
            ->andHaving('SUM(CASE WHEN jobSkills MEMBER OF resumedetails.skills THEN 1 ELSE 0 END) >= 3');
    
        $results = $qb->getQuery()->getArrayResult();
    
        // Add match score calculation and filter results
        $filteredResults = [];
        foreach ($results as $result) {
            $matchScore = 0;
            if ($result['countryMatch'] > 0) $matchScore += 1;
            if ($result['educationMatch'] > 0) $matchScore += 1;
            if ($result['experienceMatch'] > 0) $matchScore += 1;
            if ($result['skillMatchCount'] >= 5) $matchScore += 1;
    
            if ($matchScore >= 3) {
                $result['matchScore'] = $matchScore;
                $result['matchScorePercentage'] = ($matchScore / 4) * 100;
                $filteredResults[] = $result;
            }
        }
    
        // Sort the results by matchScore in descending order
        usort($filteredResults, function ($a, $b) {
            return $b['matchScore'] <=> $a['matchScore'];
        });
    
        return $filteredResults;
    }

    // public function findRecommendedCandidates(EmployerJobs $job, $minSkill, $relocate, $country, $city)
    // {
    //     $qb = $this->em->getRepository(JobSeekerResume::class)->createQueryBuilder('jr');
    //     $job_skill_count = $job->getRequiredSkill()->count();

    //     $minSkillsMatch = $minSkill && $minSkill <= $job_skill_count ? $minSkill : ($job_skill_count < 3 ? $job_skill_count : 3);
        
    //     // Skill matching condition
    //     $jobSkillsIds = $job->getRequiredSkill()->map(function($skill) { 
    //         return $skill->getId(); 
    //     })->toArray();

    //     if (!empty($jobSkillsIds)) {
    //         // Sub-query to count matched skills
    //         $qb->addSelect('COUNT(DISTINCT skills.id) AS matchedSkillsCount')
    //             ->leftJoin('jr.skills', 'skills', 'WITH', 'skills.id IN (:jobSkills)')
    //             ->setParameter('jobSkills', $jobSkillsIds)
    //             ->groupBy('jr.id')
    //             ->having('COUNT(DISTINCT skills.id) >= :minSkillsMatch')
    //             ->setParameter('minSkillsMatch', $minSkillsMatch);
    //     }
    //     // Basic conditions that always apply
    //     // $qb->select('jr', 'user', 'skills')
    //     $qb
    //     ->innerJoin('jr.jobSeeker', 'user')
    //     ->innerJoin('user.jobseekerDetails', 'details')
    //     ->where('jr.experience >= :minExperience')
    //     ->setParameter('minExperience', $job->getExperience());
        
    //     // Add education matching condition
        
    //     $qb->andWhere('jr.education >= :requiredEducation')
    //         ->setParameter('requiredEducation', $job->getEducationRequired());

    //     // Country matching logic if locals_only is true
    //     if ($job->isLocalsOnly() && !$country) {
    //         $qb->andWhere('details.country = :jobCountry')
    //             ->setParameter('jobCountry', $job->getCountry());
    //     }
    //     if($country)
    //     {
    //         $qb->andWhere('details.country = :country')
    //         ->setParameter('country', $country);
    //     }
        
    //     if($city)
    //     {
    //         $city = $this->em->getRepository(MetierCity::class)->findOneBy(['name' => $city]);
    //         $qb->andWhere('details.city = :city')
    //         ->setParameter('city', $city);
    //     }
        
    //     if($relocate === 1)
    //     {
    //         $qb->andWhere('jr.willingToRelocate = 1');
    //     }
        
    //     if($relocate === 0)
    //     {
    //         $qb->andWhere('jr.willingToRelocate = 0');
    //     }
        
    //     $qb->andWhere('jr.publicProfile = 1');
    //     // dd($qb->getQuery()->getResult());

    //     return $qb->getQuery()->getResult();
        
    // }
    public function findRecommendedCandidates(EmployerJobs $job, $minSkill, $relocate, $country, $city)
{
    // Fetch job seekers who already applied to this employer
    $excludedJobSeekers = $this->em->createQueryBuilder()
        ->select('IDENTITY(app.jobSeeker)')
        ->from(JobApplication::class, 'app')
        ->where('app.employer = :employer')
        ->setParameter('employer', $job->getEmployer())
        ->getQuery()
        ->getResult();

    // Flatten the result to an array of IDs
    $excludedJobSeekerIds = array_column($excludedJobSeekers, 1);

    // Start the main query
    $qb = $this->em->getRepository(JobSeekerResume::class)->createQueryBuilder('jr');
    $job_skill_count = $job->getRequiredSkill()->count();

    $minSkillsMatch = $minSkill && $minSkill <= $job_skill_count ? $minSkill : ($job_skill_count < 3 ? $job_skill_count : 3);

    // Skill matching condition
    $jobSkillsIds = $job->getRequiredSkill()->map(function($skill) { 
        return $skill->getId(); 
    })->toArray();

    if (!empty($jobSkillsIds)) {
        $qb->addSelect('COUNT(DISTINCT skills.id) AS matchedSkillsCount')
            ->leftJoin('jr.skills', 'skills', 'WITH', 'skills.id IN (:jobSkills)')
            ->setParameter('jobSkills', $jobSkillsIds)
            ->groupBy('jr.id')
            ->having('COUNT(DISTINCT skills.id) >= :minSkillsMatch')
            ->setParameter('minSkillsMatch', $minSkillsMatch);
    }

    // Basic conditions
    $qb
        ->innerJoin('jr.jobSeeker', 'user')
        ->innerJoin('user.jobseekerDetails', 'details')
        ->where('jr.experience >= :minExperience')
        ->setParameter('minExperience', $job->getExperience());

    $qb->andWhere('jr.education >= :requiredEducation')
        ->setParameter('requiredEducation', $job->getEducationRequired());

    // Exclude already applied job seekers
    if (!empty($excludedJobSeekerIds)) {
        $qb->andWhere($qb->expr()->notIn('user.id', ':excludedIds'))
           ->setParameter('excludedIds', $excludedJobSeekerIds);
    }

    // Country and city logic
    if ($job->isLocalsOnly() && !$country) {
        $qb->andWhere('details.country = :jobCountry')
            ->setParameter('jobCountry', $job->getCountry());
    }

    if ($country) {
        $qb->andWhere('details.country = :country')
            ->setParameter('country', $country);
    }

    if ($city) {
        $cityEntity = $this->em->getRepository(MetierCity::class)->findOneBy(['name' => $city]);
        $qb->andWhere('details.city = :city')
            ->setParameter('city', $cityEntity);
    }

    if ($relocate === 1) {
        $qb->andWhere('jr.willingToRelocate = 1');
    }

    if ($relocate === 0) {
        $qb->andWhere('jr.willingToRelocate = 0');
    }

    $qb->andWhere('jr.publicProfile = 1');

    return $qb->getQuery()->getResult();
}





    private function calculateMatchScore($resumes, EmployerJobs $job)
    {
        $filteredResults = [];
        foreach ($resumes as $resume) {
            $matchScore = 0;
            $matchedItems = [];

            // Evaluate each criteria and increment matchScore accordingly
            if ($resume->getExperience() >= $job->getExperience()) {
                $matchScore += 1;
                $matchedItems[] = 'Experience';
            }

            if ($resume->getEducation() >= $job->getEducationRequired()) {
                $matchScore += 1;
                $matchedItems[] = 'Education';
            }

            if ($job->isLocalsOnly() && $resume->getJobSeeker()->getJobseekerDetails()->getCountry() === $job->getCountry()) {
                $matchScore += 1;
                $matchedItems[] = 'Country';
            }

            // Add logic for skills if applicable
            // Assumed that you can extract matched skills count somehow

            if ($matchScore >= 3) {  // Adjust the criteria as per your need
                $resumeData = [
                    'resume' => $resume,
                    'matchScore' => $matchScore,
                    'matchedItems' => $matchedItems
                ];
                $filteredResults[] = $resumeData;
            }
        }

        return $filteredResults;
    }
    
    public function findJobsClosingToday(\DateTime $today)
    {
         // Get the start and end of the day for the given date
    $startOfDay = (clone $today)->setTime(0, 0, 0);
    $endOfDay = (clone $today)->setTime(23, 59, 59);

    return $this->createQueryBuilder('j')
        ->where('j.application_closing_date BETWEEN :start AND :end')
        ->setParameter('start', $startOfDay)
        ->setParameter('end', $endOfDay)
        ->getQuery()
        ->getResult();
    }
}