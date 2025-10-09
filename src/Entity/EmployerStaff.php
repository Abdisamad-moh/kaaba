<?php

namespace App\Entity;

use App\Repository\EmployerStaffRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployerStaffRepository::class)]
class EmployerStaff
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobseeker = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'employerStaff')]
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

    public function setJobseeker(User $jobseeker): static
    {
        $this->jobseeker = $jobseeker;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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
