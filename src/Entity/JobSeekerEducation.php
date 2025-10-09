<?php

namespace App\Entity;

use App\Repository\JobSeekerEducationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobSeekerEducationRepository::class)]
class JobSeekerEducation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?bool $enrolled = null;

    #[ORM\Column(length: 255)]
    private ?string $school = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $course = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $specialization = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $courseType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerEducation')]
    private ?MetierCity $city = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerEducation')]
    private ?MetierCountry $country = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerEducation')]
    private ?MetierState $state = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fromYear = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $toYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gradingSystem = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerEducation')]
    private ?User $jobSeeker = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isEnrolled(): ?bool
    {
        return $this->enrolled;
    }

    public function setEnrolled(?bool $enrolled): static
    {
        $this->enrolled = $enrolled;

        return $this;
    }

    public function getSchool(): ?string
    {
        return $this->school;
    }

    public function setSchool(string $school): static
    {
        $this->school = $school;

        return $this;
    }

    public function getCourse(): ?string
    {
        return $this->course;
    }

    public function setCourse(?string $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getSpecialization(): ?string
    {
        return $this->specialization;
    }

    public function setSpecialization(?string $specialization): static
    {
        $this->specialization = $specialization;

        return $this;
    }

    public function getCourseType(): ?string
    {
        return $this->courseType;
    }

    public function setCourseType(?string $courseType): static
    {
        $this->courseType = $courseType;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?MetierCity
    {
        return $this->city;
    }

    public function setCity(?MetierCity $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?MetierCountry
    {
        return $this->country;
    }

    public function setCountry(?MetierCountry $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getState(): ?MetierState
    {
        return $this->state;
    }

    public function setState(?MetierState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getFromYear(): ?\DateTimeInterface
    {
        return $this->fromYear;
    }

    public function setFromYear(?\DateTimeInterface $fromYear): static
    {
        $this->fromYear = $fromYear;

        return $this;
    }

    public function getToYear(): ?\DateTimeInterface
    {
        return $this->toYear;
    }

    public function setToYear(?\DateTimeInterface $toYear): static
    {
        $this->toYear = $toYear;

        return $this;
    }

    public function getGradingSystem(): ?string
    {
        return $this->gradingSystem;
    }

    public function setGradingSystem(?string $gradingSystem): static
    {
        $this->gradingSystem = $gradingSystem;

        return $this;
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

    public function getCourseTypeName(): ?string
    {
        return match ($this->courseType) {
            'fulltime' => 'Full Time',
            'part_time' => 'Part Time',
            'distance_learning' => 'Distance Learning',
            default => '',
        };
    }
}
