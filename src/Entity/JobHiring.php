<?php

namespace App\Entity;

use App\Repository\JobHiringRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobHiringRepository::class)]
class JobHiring
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?JobApplication $application = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    private ?string $salary_package = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $joining_date = null;

    #[ORM\Column]
    private ?int $probation_period = null;

    #[ORM\ManyToOne(inversedBy: 'employerHirings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $employe = null;

    #[ORM\ManyToOne(inversedBy: 'jobseekerHirings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobseeker = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplication(): ?JobApplication
    {
        return $this->application;
    }

    public function setApplication(JobApplication $application): static
    {
        $this->application = $application;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSalaryPackage(): ?string
    {
        return $this->salary_package;
    }

    public function setSalaryPackage(string $salary_package): static
    {
        $this->salary_package = $salary_package;

        return $this;
    }

    public function getJoiningDate(): ?\DateTimeInterface
    {
        return $this->joining_date;
    }

    public function setJoiningDate(\DateTimeInterface $joining_date): static
    {
        $this->joining_date = $joining_date;

        return $this;
    }

    public function getProbationPeriod(): ?int
    {
        return $this->probation_period;
    }

    public function setProbationPeriod(int $probation_period): static
    {
        $this->probation_period = $probation_period;

        return $this;
    }

    public function getEmploye(): ?User
    {
        return $this->employe;
    }

    public function setEmploye(?User $employe): static
    {
        $this->employe = $employe;

        return $this;
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
}
