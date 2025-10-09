<?php

namespace App\Entity;

use App\Repository\JobSeekerExperienceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobSeekerExperienceRepository::class)]
class JobSeekerExperience
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerExperiences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobSeeker = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerExperiences')]
    private ?MetierJobType $jobType = null;

    #[ORM\Column(nullable: true)]
    private ?int $experienceYears = null;

    #[ORM\Column(nullable: true)]
    private ?int $experienceMonths = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currentCompany = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currentOrganization = null;

    #[ORM\Column(nullable: true)]
    private ?int $joinedYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $joinedMonth = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $noticePeriod = null;

    #[ORM\Column(nullable: true)]
    private ?string $salary = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totalExperience = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $joinedDate = null;

    #[ORM\Column(length: 255)]
    private ?string $companyName = null;

    #[ORM\Column]
    private ?bool $current = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $finishDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $duties = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerExperiences')]
    private ?MetierCountry $country = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerExperiences')]
    private ?MetierState $state = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerExperiences')]
    private ?MetierCity $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $positionName = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerExperiences')]
    private ?MetierCurrency $currency = null;

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

    public function getJobType(): ?MetierJobType
    {
        return $this->jobType;
    }

    public function setJobType(?MetierJobType $jobType): static
    {
        $this->jobType = $jobType;

        return $this;
    }

    public function getExperienceYears(): ?int
    {
        return $this->experienceYears;
    }

    public function setExperienceYears(?int $experienceYears): static
    {
        $this->experienceYears = $experienceYears;

        return $this;
    }

    public function getExperienceMonths(): ?int
    {
        return $this->experienceMonths;
    }

    public function setExperienceMonths(?int $experienceMonths): static
    {
        $this->experienceMonths = $experienceMonths;

        return $this;
    }

    public function getCurrentCompany(): ?string
    {
        return $this->currentCompany;
    }

    public function setCurrentCompany(?string $currentCompany): static
    {
        $this->currentCompany = $currentCompany;

        return $this;
    }

    public function getCurrentOrganization(): ?string
    {
        return $this->currentOrganization;
    }

    public function setCurrentOrganization(?string $currentOrganization): static
    {
        $this->currentOrganization = $currentOrganization;

        return $this;
    }

    public function getJoinedYear(): ?int
    {
        return $this->joinedYear;
    }

    public function setJoinedYear(?int $joinedYear): static
    {
        $this->joinedYear = $joinedYear;

        return $this;
    }

    public function getJoinedMonth(): ?int
    {
        return $this->joinedMonth;
    }

    public function setJoinedMonth(?int $joinedMonth): static
    {
        $this->joinedMonth = $joinedMonth;

        return $this;
    }

    public function getNoticePeriod(): ?string
    {
        return $this->noticePeriod;
    }

    public function setNoticePeriod(?string $noticePeriod): static
    {
        $this->noticePeriod = $noticePeriod;

        return $this;
    }

    public function getSalary(): ?string
    {
        return $this->salary;
    }

    public function setSalary(?string $salary): static
    {
        $this->salary = $salary;

        return $this;
    }

    public function getTotalExperience(): ?string
    {
        return $this->totalExperience;
    }

    public function setTotalExperience(?string $totalExperience): static
    {
        $this->totalExperience = $totalExperience;

        return $this;
    }

    public function getJoinedDate(): ?\DateTimeInterface
    {
        return $this->joinedDate;
    }

    public function setJoinedDate(?\DateTimeInterface $joinedDate): static
    {
        $this->joinedDate = $joinedDate;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function isCurrent(): ?bool
    {
        return $this->current;
    }

    public function setCurrent(bool $current): static
    {
        $this->current = $current;

        return $this;
    }

    public function getFinishDate(): ?\DateTimeInterface
    {
        return $this->finishDate;
    }

    public function setFinishDate(?\DateTimeInterface $finishDate): static
    {
        $this->finishDate = $finishDate;

        return $this;
    }

    public function getDuties(): ?string
    {
        return $this->duties;
    }

    public function setDuties(?string $duties): static
    {
        $this->duties = $duties;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

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

    public function getCity(): ?MetierCity
    {
        return $this->city;
    }

    public function setCity(?MetierCity $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPositionName(): ?string
    {
        return $this->positionName;
    }

    public function setPositionName(?string $positionName): static
    {
        $this->positionName = $positionName;

        return $this;
    }

    public function getCurrency(): ?MetierCurrency
    {
        return $this->currency;
    }

    public function setCurrency(?MetierCurrency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }
}
