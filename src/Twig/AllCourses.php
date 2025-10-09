<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Entity\EmployerJobs;
use App\Entity\JobSeekerSavedJob;
use App\Repository\EmployerCoursesRepository;
use App\Service\EmojiCollection;
use App\Repository\EmployerJobsRepository;
use App\Repository\EmployerTenderRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('all-courses')]
class AllCourses
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public $course_title;
    #[LiveProp(writable: true)]
    public $country_id;
    private const PER_PAGE = 3;
    private const PER_PAGE_HOME = 3;

    #[LiveProp]
    public int $page = 1;

    #[LiveProp]
    public array $courses_list = [];

    #[LiveProp(writable: true)]
    public bool $home = false;
    private bool $has_more = true;
    #[LiveProp]
    public array $saved_jobs = [];

    public function __construct(
        private readonly EmployerCoursesRepository $courses, 
        private EntityManagerInterface $em,
        private Security $security
        )
    {
    
    }


    #[LiveAction]
    public function more(): void
    {
        ++$this->page;
    }

    public function hasMore(): bool
    {
        return $this->has_more;
    }

    public function getCourses(): array
    {
        $now = new \DateTime();
        
        $courses = $this->courses->getJobPaginator(0, $this->page * ($this->home ? self::PER_PAGE_HOME : self::PER_PAGE), false, $this->course_title, $this->country_id)
                ->getQuery()->getResult();
        if($this->home == false):
            $check_for_more = $this->courses->getJobPaginator(0, ($this->page + 1) * self::PER_PAGE, true, $this->course_title, $this->country_id)->getQuery()->getSingleScalarResult();
            if($check_for_more == count($courses)) $this->has_more = false;
        endif;
        

        return $courses;
    }

    #[LiveAction]
    public function saveJob(#[LiveArg] int $id)
    {
        $job = $this->em->getRepository(EmployerJobs::class)->find($id);
        if($job)
        {
            $already_saved = $this->em->getRepository(JobSeekerSavedJob::class)->findOneBy(['job' => $job, 'jobSeeker' => $this->security->getUser()]);
            
            if($already_saved) {
                $this->em->remove($already_saved);
                $this->em->flush();
                $this->dispatchBrowserEvent('unSavedJob', ['job' => $job->getId()]);
                return;
            }

            $jobseeker_saved_job = new JobSeekerSavedJob();
            $jobseeker_saved_job->setJob($job);
            $jobseeker_saved_job->setJobSeeker($this->security->getUser());
            $this->em->persist($jobseeker_saved_job);
            $this->em->flush();
            
            $this->dispatchBrowserEvent('savedJob', ['job' => $job->getId()]);
            return;
        }
    }

    public function mount()
    {
        $saved_jobs = $this->em->getRepository(JobSeekerSavedJob::class)
            ->createQueryBuilder('s')
            ->join('s.job', 'j')
            ->where('s.jobSeeker = :jobSeeker')
            ->setParameter('jobSeeker', $this->security->getUser())
            ->select('j.id')
            ->getQuery()
            ->getResult()
        ;

        $this->saved_jobs = array_column($saved_jobs, 'id');
    }
}
