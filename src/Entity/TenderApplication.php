<?php

namespace App\Entity;

use App\Repository\TenderApplicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TenderApplicationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TenderApplication
{
    use Timestamps;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tenderApplications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $applicant = null;

    #[ORM\Column(length: 255)]
    private ?string $company_name = null;

    #[ORM\Column(length: 255)]
    private ?string $company_phone = null;

    #[ORM\ManyToOne(inversedBy: 'tenderApplications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierCountry $country = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'tenderApplications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EmployerTender $tender = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplicant(): ?User
    {
        return $this->applicant;
    }

    public function setApplicant(?User $applicant): static
    {
        $this->applicant = $applicant;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->company_name;
    }

    public function setCompanyName(string $company_name): static
    {
        $this->company_name = $company_name;

        return $this;
    }
    
    public function getCompanyPhone(): ?string
    {
        return $this->company_phone;
    }

    public function setCompanyPhone(string $company_phone): static
    {
        $this->company_phone = $company_phone;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTender(): ?EmployerTender
    {
        return $this->tender;
    }

    public function setTender(?EmployerTender $tender): static
    {
        $this->tender = $tender;

        return $this;
    }

    public function getCompanyEmail(): ?string
    {
        return $this->companyEmail;
    }

    public function setCompanyEmail(?string $companyEmail): static
    {
        $this->companyEmail = $companyEmail;

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
}
