<?php

namespace App\Entity;

use App\Repository\JobReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobReportRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_job_report', columns: ["job_id", "reported_by_id"])]
class JobReport
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'jobReports')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EmployerJobs $job = null;

    #[ORM\ManyToOne(inversedBy: 'jobReports')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $reportedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getReportedBy(): ?User
    {
        return $this->reportedBy;
    }

    public function setReportedBy(?User $reportedBy): static
    {
        $this->reportedBy = $reportedBy;

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
}
