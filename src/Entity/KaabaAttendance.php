<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\KaabaAttendanceRepository;

#[ORM\Entity(repositoryClass: KaabaAttendanceRepository::class)]
class KaabaAttendance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attendances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?KaabaApplication $application = null;

    #[ORM\ManyToOne(inversedBy: 'attendances')]
    #[ORM\JoinColumn(nullable: true)]
    private ?KaabaBiotimeDevice $device = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $check_in_time = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $check_out_time = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $attendance_date = null;

    #[ORM\Column(nullable: true)]
    private ?float $total_hours = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'present'; // present, late, absent, half-day, holiday, leave

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $biotime_transaction_id = null;

    #[ORM\ManyToOne(inversedBy: 'verifiedAttendances')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $verified_by = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $attendance_type = 'biometric'; // biometric, manual, qr_code, card

    #[ORM\Column(nullable: true)]
    private ?bool $is_verified = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $verification_notes = null;

    #[ORM\ManyToOne(inversedBy: 'attendances')]
    private ?KaabaInstitute $institute = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->attendance_date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // Add this method to KaabaAttendance entity
public function calculateTotalHoursWithVirtualCheckout(): ?float
{
    if (!$this->check_in_time) {
        return null;
    }
    
    // If we have a check-out time, use it
    if ($this->check_out_time) {
        $interval = $this->check_in_time->diff($this->check_out_time);
        return $interval->h + ($interval->i / 60) + ($interval->s / 3600);
    }
    
    // If this is the last record of the day for this application,
    // we need to use this check-in time as virtual check-out
    // This should be handled at the controller level
    
    return 0;
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

    public function getDevice(): ?KaabaBiotimeDevice
    {
        return $this->device;
    }

    public function setDevice(?KaabaBiotimeDevice $device): static
    {
        $this->device = $device;

        return $this;
    }

    public function getCheckInTime(): ?\DateTimeInterface
    {
        return $this->check_in_time;
    }

    public function setCheckInTime(\DateTimeInterface $check_in_time): static
    {
        $this->check_in_time = $check_in_time;

        return $this;
    }

    public function getCheckOutTime(): ?\DateTimeInterface
    {
        return $this->check_out_time;
    }

    public function setCheckOutTime(?\DateTimeInterface $check_out_time): static
    {
        $this->check_out_time = $check_out_time;

        return $this;
    }

    public function getAttendanceDate(): ?\DateTimeInterface
    {
        return $this->attendance_date;
    }

    public function setAttendanceDate(\DateTimeInterface $attendance_date): static
    {
        $this->attendance_date = $attendance_date;

        return $this;
    }

    public function getTotalHours(): ?float
    {
        return $this->total_hours;
    }

    public function setTotalHours(?float $total_hours): static
    {
        $this->total_hours = $total_hours;

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

    public function getBiotimeTransactionId(): ?string
    {
        return $this->biotime_transaction_id;
    }

    public function setBiotimeTransactionId(?string $biotime_transaction_id): static
    {
        $this->biotime_transaction_id = $biotime_transaction_id;

        return $this;
    }

    public function getVerifiedBy(): ?User
    {
        return $this->verified_by;
    }

    public function setVerifiedBy(?User $verified_by): static
    {
        $this->verified_by = $verified_by;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

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

    public function getAttendanceType(): ?string
    {
        return $this->attendance_type;
    }

    public function setAttendanceType(?string $attendance_type): static
    {
        $this->attendance_type = $attendance_type;

        return $this;
    }

    public function isIsVerified(): ?bool
    {
        return $this->is_verified;
    }

    public function setIsVerified(?bool $is_verified): static
    {
        $this->is_verified = $is_verified;

        return $this;
    }

    public function getVerificationNotes(): ?string
    {
        return $this->verification_notes;
    }

    public function setVerificationNotes(?string $verification_notes): static
    {
        $this->verification_notes = $verification_notes;

        return $this;
    }

    // Helper method to calculate total hours
    public function calculateTotalHours(): void
    {
        if ($this->check_in_time && $this->check_out_time) {
            $interval = $this->check_in_time->diff($this->check_out_time);
            $this->total_hours = $interval->h + ($interval->i / 60);
        }
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
}