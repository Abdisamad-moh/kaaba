<?php

namespace App\Entity;

use App\Repository\MetierProfileViewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierProfileViewRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MetierProfileView
{
    use Timestamps;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'metierProfileViews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobseeker = null;

    #[ORM\ManyToOne(inversedBy: 'employerProfileViews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $employer = null;

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
}
