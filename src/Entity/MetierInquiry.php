<?php

namespace App\Entity;

use App\Repository\MetierInquiryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierInquiryRepository::class)]
class MetierInquiry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $company_name = null;

    #[ORM\Column(length: 255)]
    private ?string $contact_person = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    private ?string $job_title = null;

    #[ORM\Column(length: 255)]
    private ?string $department = null;

    #[ORM\ManyToOne(inversedBy: 'metierInquiries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierCountry $country = null;

    #[ORM\Column(length: 255)]
    private ?string $purpose_of_evaluation = null;

    #[ORM\Column(length: 255)]
    private ?string $estimated_budget = null;

    #[ORM\Column(length: 255)]
    private ?string $payment_method = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;


    public function getId(): ?int
    {
        return $this->id;
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

    public function getContactPerson(): ?string
    {
        return $this->contact_person;
    }

    public function setContactPerson(string $contact_person): static
    {
        $this->contact_person = $contact_person;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->job_title;
    }

    public function setJobTitle(string $job_title): static
    {
        $this->job_title = $job_title;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(string $department): static
    {
        $this->department = $department;

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

    public function getPurposeOfEvaluation(): ?string
    {
        return $this->purpose_of_evaluation;
    }

    public function setPurposeOfEvaluation(string $purpose_of_evaluation): static
    {
        $this->purpose_of_evaluation = $purpose_of_evaluation;

        return $this;
    }

    public function getEstimatedBudget(): ?string
    {
        return $this->estimated_budget;
    }

    public function setEstimatedBudget(string $estimated_budget): static
    {
        $this->estimated_budget = $estimated_budget;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->payment_method;
    }

    public function setPaymentMethod(string $payment_method): static
    {
        $this->payment_method = $payment_method;

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

    
}
