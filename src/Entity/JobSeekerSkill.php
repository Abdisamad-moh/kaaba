<?php

namespace App\Entity;

use App\Repository\JobSeekerSkillRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobSeekerSkillRepository::class)]
class JobSeekerSkill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerSkills')]
    private ?User $jobseeker = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerSkills')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierSkills $skill = null;

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

    public function getSkill(): ?MetierSkills
    {
        return $this->skill;
    }

    public function setSkill(?MetierSkills $skill): static
    {
        $this->skill = $skill;

        return $this;
    }
}
