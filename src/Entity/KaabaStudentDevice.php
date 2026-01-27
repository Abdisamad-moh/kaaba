<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\KaabaStudentDeviceRepository;

#[ORM\Entity(repositoryClass: KaabaStudentDeviceRepository::class)]
class KaabaStudentDevice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'studentDevice', targetEntity: KaabaApplication::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?KaabaApplication $application = null;

    #[ORM\ManyToOne(inversedBy: 'studentDevices')]
    #[ORM\JoinColumn(nullable: true)]
    private ?KaabaBiotimeDevice $device = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $biotime_employee_id = null; // Biotime's internal employee ID

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $biotime_fingerprint_template = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $biotime_face_template = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $enrollment_date = null;

    #[ORM\Column(length: 20)]
    private ?string $enrollment_status = 'enrolled'; // enrolled, active, suspended, graduated

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $last_attendance_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $card_number = null; // For card-based attendance

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $enrollment_notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $biotime_enrollment_response = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->enrollment_date = new \DateTime();
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

    public function getDevice(): ?KaabaBiotimeDevice
    {
        return $this->device;
    }

    public function setDevice(?KaabaBiotimeDevice $device): static
    {
        $this->device = $device;

        return $this;
    }

    public function getBiotimeEmployeeId(): ?string
    {
        return $this->biotime_employee_id;
    }

    public function setBiotimeEmployeeId(?string $biotime_employee_id): static
    {
        $this->biotime_employee_id = $biotime_employee_id;

        return $this;
    }

    public function getBiotimeFingerprintTemplate(): ?string
    {
        return $this->biotime_fingerprint_template;
    }

    public function setBiotimeFingerprintTemplate(?string $biotime_fingerprint_template): static
    {
        $this->biotime_fingerprint_template = $biotime_fingerprint_template;

        return $this;
    }

    public function getBiotimeFaceTemplate(): ?string
    {
        return $this->biotime_face_template;
    }

    public function setBiotimeFaceTemplate(?string $biotime_face_template): static
    {
        $this->biotime_face_template = $biotime_face_template;

        return $this;
    }

    public function getEnrollmentDate(): ?\DateTimeInterface
    {
        return $this->enrollment_date;
    }

    public function setEnrollmentDate(\DateTimeInterface $enrollment_date): static
    {
        $this->enrollment_date = $enrollment_date;

        return $this;
    }

    public function getEnrollmentStatus(): ?string
    {
        return $this->enrollment_status;
    }

    public function setEnrollmentStatus(string $enrollment_status): static
    {
        $this->enrollment_status = $enrollment_status;

        return $this;
    }

    public function getLastAttendanceDate(): ?\DateTimeInterface
    {
        return $this->last_attendance_date;
    }

    public function setLastAttendanceDate(?\DateTimeInterface $last_attendance_date): static
    {
        $this->last_attendance_date = $last_attendance_date;

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

    public function getCardNumber(): ?string
    {
        return $this->card_number;
    }

    public function setCardNumber(?string $card_number): static
    {
        $this->card_number = $card_number;

        return $this;
    }

    public function getEnrollmentNotes(): ?string
    {
        return $this->enrollment_notes;
    }

    public function setEnrollmentNotes(?string $enrollment_notes): static
    {
        $this->enrollment_notes = $enrollment_notes;

        return $this;
    }

    public function getBiotimeEnrollmentResponse(): ?string
    {
        return $this->biotime_enrollment_response;
    }

    public function setBiotimeEnrollmentResponse(?string $biotime_enrollment_response): static
    {
        $this->biotime_enrollment_response = $biotime_enrollment_response;

        return $this;
    }
}