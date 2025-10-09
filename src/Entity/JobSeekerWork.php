<?php

namespace App\Entity;

use App\Repository\JobSeekerWorkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobSeekerWorkRepository::class)]
class JobSeekerWork
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerWorks', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobSeeker = null;

    #[ORM\Column(nullable: true)]
    private ?string $experience = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerWorks')]
    private ?MetierCareers $profession = null;

    #[ORM\Column(nullable: true)]
    private ?float $salary = null;

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

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(?string $experience): static
    {
        $this->experience = $experience;

        return $this;
    }

    public function getProfession(): ?MetierCareers
    {
        return $this->profession;
    }

    public function setProfession(?MetierCareers $profession): static
    {
        $this->profession = $profession;

        return $this;
    }

    public function getSalary(): ?float
    {
        return $this->salary;
    }

    public function setSalary(?float $salary): static
    {
        $this->salary = $salary;

        return $this;
    }
}
