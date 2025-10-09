<?php

namespace App\Entity;

use App\Repository\JobOfferingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobOfferingRepository::class)]
class JobOffering
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?JobApplication $application = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $salary = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $joining_date = null;

    #[ORM\Column]
    private ?int $probation_period = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $offer_letter = null;

    #[ORM\ManyToOne(inversedBy: 'jobOfferings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobseeker = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'employerJobOferrings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $employer = null;

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

    public function getSalary(): ?string
    {
        return $this->salary;
    }

    public function setSalary(string $salary): static
    {
        $this->salary = $salary;

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

    public function getOfferLetter(): ?string
    {
        return $this->offer_letter;
    }

    public function setOfferLetter(?string $offer_letter): static
    {
        $this->offer_letter = $offer_letter;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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
}
