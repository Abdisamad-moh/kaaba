<?php

namespace App\Entity;

use App\Repository\JobSeekerRecommendedJobsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobSeekerRecommendedJobsRepository::class)]
class JobSeekerRecommendedJobs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'recommendedJobs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobseeker = null;

    #[ORM\ManyToOne(inversedBy: 'employerRecommendedJobs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $employer = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerRecommendedJobs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EmployerJobs $job = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobseeker(): ?User
    {
        return $this->jobseeker;
    }

    public function setJobseeker(?User $jobseeker): static
    {
        $this->jobseeker = $jobseeker;

        return $this;
    }

    public function getEmployer(): ?User
    {
        return $this->employer;
    }

    public function setEmployer(?User $employer): static
    {
        $this->employer = $employer;

        return $this;
    }

    public function getJob(): ?EmployerJobs
    {
        return $this->job;
    }

    public function setJob(?EmployerJobs $job): static
    {
        $this->job = $job;

        return $this;
    }
}
