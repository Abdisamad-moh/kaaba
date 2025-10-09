<?php

namespace App\Entity;

use App\Repository\JobSeekerSavedJobRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobSeekerSavedJobRepository::class)]
class JobSeekerSavedJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerSavedJobs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobSeeker = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerSavedJobs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EmployerJobs $job = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobSeeker(): ?User
    {
        return $this->jobSeeker;
    }

    public function setJobSeeker(?User $jobSeeker): static
    {
        $this->jobSeeker = $jobSeeker;

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
