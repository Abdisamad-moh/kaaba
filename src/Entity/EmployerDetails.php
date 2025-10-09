<?php

namespace App\Entity;

use App\Repository\EmployerDetailsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployerDetailsRepository::class)]
class EmployerDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'employerDetails', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $employer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $heading = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\ManyToOne(inversedBy: 'employers')]
    private ?MetierJobIndustry $industry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $social_facebook = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $social_twitter = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $social_linkedin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $company_stablishment_date = null;

    #[ORM\ManyToOne(inversedBy: 'companies')]
    private ?MetierCountry $country = null;

    #[ORM\ManyToOne(inversedBy: 'companies')]
    private ?MetierCity $city = null;

    #[ORM\ManyToOne(inversedBy: 'companies')]
    private ?MetierState $state = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zipcode = null;

    #[ORM\Column(nullable: true)]
    private ?string $number_of_employees = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployer(): ?User
    {
        return $this->employer;
    }

    public function setEmployer(User $employer): static
    {
        $this->employer = $employer;

        return $this;
    }

    public function getHeading(): ?string
    {
        return $this->heading;
    }

    public function setHeading(?string $heading): static
    {
        $this->heading = $heading;

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

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getIndustry(): ?MetierJobIndustry
    {
        return $this->industry;
    }

    public function setIndustry(?MetierJobIndustry $industry): static
    {
        $this->industry = $industry;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getSocialFacebook(): ?string
    {
        return $this->social_facebook;
    }

    public function setSocialFacebook(?string $social_facebook): static
    {
        $this->social_facebook = $social_facebook;

        return $this;
    }

    public function getSocialTwitter(): ?string
    {
        return $this->social_twitter;
    }

    public function setSocialTwitter(?string $social_twitter): static
    {
        $this->social_twitter = $social_twitter;

        return $this;
    }

    public function getSocialLinkedin(): ?string
    {
        return $this->social_linkedin;
    }

    public function setSocialLinkedin(?string $social_linkedin): static
    {
        $this->social_linkedin = $social_linkedin;

        return $this;
    }

    public function getCompanyStablishmentDate(): ?\DateTimeInterface
    {
        return $this->company_stablishment_date;
    }

    public function setCompanyStablishmentDate(?\DateTimeInterface $company_stablishment_date): static
    {
        $this->company_stablishment_date = $company_stablishment_date;

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

    public function getCity(): ?MetierCity
    {
        return $this->city;
    }

    public function setCity(?MetierCity $city): static
    {
        $this->city = $city;

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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(?string $zipcode): static
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    public function getNumberOfEmployees(): ?string
    {
        return $this->number_of_employees;
    }

    public function setNumberOfEmployees(?string $number_of_employees): static
    {
        $this->number_of_employees = $number_of_employees;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

   
}
