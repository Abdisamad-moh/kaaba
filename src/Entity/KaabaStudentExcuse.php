<?php
// src/Entity/KaabaStudentExcuse.php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\KaabaStudentExcuseRepository;

#[ORM\Entity(repositoryClass: KaabaStudentExcuseRepository::class)]
#[ORM\Table(name: 'kaaba_student_excuse')]
class KaabaStudentExcuse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'excuses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?KaabaApplication $application = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?KaabaInstitute $institute = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $end_date = null;

    #[ORM\Column(length: 50)]
    private ?string $excuse_type = null; // sick, personal, family, travel, other

    #[ORM\Column(type: Types::TEXT)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $admin_notes = null;

    #[ORM\Column]
    private ?bool $is_approved = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $approved_by = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $approved_at = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $created_by = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(nullable: true)]
    private ?float $excused_hours_per_day = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachment = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplication(): ?KaabaApplication
    {
        return $this->application;
    }

    public function setApplication(?KaabaApplication $application): static
    {
        $this->application = $application;
        return $this;
    }

    public function getInstitute(): ?KaabaInstitute
    {
        return $this->institute;
    }

    public function setInstitute(?KaabaInstitute $institute): static
    {
        $this->institute = $institute;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(\DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEndDate(?\DateTimeInterface $end_date): static
    {
        $this->end_date = $end_date;
        return $this;
    }

    public function getExcuseType(): ?string
    {
        return $this->excuse_type;
    }

    public function setExcuseType(string $excuse_type): static
    {
        $this->excuse_type = $excuse_type;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getAdminNotes(): ?string
    {
        return $this->admin_notes;
    }

    public function setAdminNotes(?string $admin_notes): static
    {
        $this->admin_notes = $admin_notes;
        return $this;
    }

    public function isIsApproved(): ?bool
    {
        return $this->is_approved;
    }

    public function setIsApproved(bool $is_approved): static
    {
        $this->is_approved = $is_approved;
        return $this;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approved_by;
    }

    public function setApprovedBy(?User $approved_by): static
    {
        $this->approved_by = $approved_by;
        return $this;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approved_at;
    }

    public function setApprovedAt(?\DateTimeInterface $approved_at): static
    {
        $this->approved_at = $approved_at;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->created_by;
    }

    public function setCreatedBy(?User $created_by): static
    {
        $this->created_by = $created_by;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getExcusedHoursPerDay(): ?float
    {
        return $this->excused_hours_per_day;
    }

    public function setExcusedHoursPerDay(?float $excused_hours_per_day): static
    {
        $this->excused_hours_per_day = $excused_hours_per_day;
        return $this;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(?string $attachment): static
    {
        $this->attachment = $attachment;
        return $this;
    }

    // Helper method to get all dates in the excuse range
    public function getExcuseDates(): array
    {
        $dates = [];
        $current = clone $this->start_date;
        $end = $this->end_date ?? clone $this->start_date;
        
        while ($current <= $end) {
            $dates[] = clone $current;
            $current->modify('+1 day');
        }
        
        return $dates;
    }

    // Helper method to check if a specific date is in the excuse range
    public function coversDate(\DateTimeInterface $date): bool
    {
        $end = $this->end_date ?? $this->start_date;
        return $date >= $this->start_date && $date <= $end;
    }

    // Get formatted date range string
    public function getDateRangeString(): string
    {
        if ($this->end_date && $this->end_date != $this->start_date) {
            return $this->start_date->format('M d, Y') . ' - ' . $this->end_date->format('M d, Y');
        }
        return $this->start_date->format('M d, Y');
    }

    // Get total excused days
    public function getTotalExcusedDays(): int
    {
        $end = $this->end_date ?? $this->start_date;
        $interval = $this->start_date->diff($end);
        return $interval->days + 1;
    }

    // Get total excused hours
    public function getTotalExcusedHours(): float
    {
        if ($this->excused_hours_per_day) {
            return $this->getTotalExcusedDays() * $this->excused_hours_per_day;
        }
        return 0;
    }
}