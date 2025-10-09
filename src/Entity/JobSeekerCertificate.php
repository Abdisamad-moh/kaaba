<?php

namespace App\Entity;

use App\Repository\JobSeekerCertificateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobSeekerCertificateRepository::class)]
class JobSeekerCertificate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerCertificates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobSeeker = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $certificateId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $certificateUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $file = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $expirable = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $institute = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerCertificates')]
    private ?MetierCity $city = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerCertificates')]
    private ?MetierCountry $country = null;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCertificateId(): ?string
    {
        return $this->certificateId;
    }

    public function setCertificateId(?string $certificateId): static
    {
        $this->certificateId = $certificateId;

        return $this;
    }

    public function getCertificateUrl(): ?string
    {
        return $this->certificateUrl;
    }

    public function setCertificateUrl(?string $certificateUrl): static
    {
        $this->certificateUrl = $certificateUrl;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(?string $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function isExpirable(): ?bool
    {
        return $this->expirable;
    }

    public function setExpirable(bool $expirable): static
    {
        $this->expirable = $expirable;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getInstitute(): ?string
    {
        return $this->institute;
    }

    public function setInstitute(?string $institute): static
    {
        $this->institute = $institute;

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
}
