<?php

namespace App\Entity;

use App\Model\ResumeStatusEnum;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\JobseekerDetailsRepository;

#[ORM\Entity(repositoryClass: JobseekerDetailsRepository::class)]
class JobseekerDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'jobseekerDetails', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobseeker = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dob = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\ManyToOne(inversedBy: 'jobseekerDetails')]
    private ?MetierGender $gender = null;

    #[ORM\ManyToOne(inversedBy: 'jobseekerDetails')]
    private ?MetierState $state = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $whatsappPhone = null;

    #[ORM\ManyToOne(inversedBy: 'jobseekerDetails')]
    private ?MetierCareers $profession = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $experience = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $salary = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aboutMe = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resumeHeadline = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cv = null;

    /**
     * @var Collection<int, MetierSkills>
     */
    #[ORM\ManyToMany(targetEntity: MetierSkills::class, inversedBy: 'jobseekerDetails')]
    private Collection $skills;

    #[ORM\Column(length: 255, nullable: true, options: ['default' => false])]
    private ?bool $open_to_work = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkedin_link = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $portfolio_link = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 255, nullable: true, enumType: ResumeStatusEnum::class)]
    private ?ResumeStatusEnum $careerStatus = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $middleName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact_type = null;

    #[ORM\ManyToOne(inversedBy: 'yes')]
    private ?MetierCountry $country = null;

    #[ORM\ManyToOne]
    private ?MetierCity $city = null;



    

    public function __construct()
    {
        $this->skills = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobseeker(): ?User
    {
        return $this->jobseeker;
    }

    public function setJobseeker(User $jobseeker): static
    {
        $this->jobseeker = $jobseeker;

        return $this;
    }

    public function getDob(): ?\DateTimeInterface
    {
        return $this->dob;
    }

    public function setDob(?\DateTimeInterface $dob): static
    {
        $this->dob = $dob;

        return $this;
    }

    public function age() {
        $birthDate = $this->dob;
        $today = new DateTime('today');
        $age = $today->diff($birthDate)->y;
        return $age . " Years old";
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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getGender(): ?MetierGender
    {
        return $this->gender;
    }

    public function setGender(?MetierGender $gender): static
    {
        $this->gender = $gender;

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

    public function getWhatsappPhone(): ?string
    {
        return $this->whatsappPhone;
    }

    public function setWhatsappPhone(?string $whatsappPhone): static
    {
        $this->whatsappPhone = $whatsappPhone;

        return $this;
    }

    public function getProfession(): ?MetierCareers
    {
        return $this->profession;
    }

    public function setProfession(?MetierCareers $profession): static
    {
        $this->profession = $profession;

        return $this;
    }

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(?string $experience): static
    {
        $this->experience = $experience;

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

    public function getAboutMe(): ?string
    {
        return $this->aboutMe;
    }

    public function setAboutMe(?string $aboutMe): static
    {
        $this->aboutMe = $aboutMe;

        return $this;
    }

    public function getResumeHeadline(): ?string
    {
        return $this->resumeHeadline;
    }

    public function setResumeHeadline(?string $resumeHeadline): static
    {
        $this->resumeHeadline = $resumeHeadline;

        return $this;
    }

    public function getCv(): ?string
    {
        return $this->cv;
    }

    public function setCv(?string $cv): static
    {
        $this->cv = $cv;

        return $this;
    }

    /**
     * @return Collection<int, MetierSkills>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(MetierSkills $skill): static
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(MetierSkills $skill): static
    {
        $this->skills->removeElement($skill);

        return $this;
    }

    public function getOpenToWork(): ?bool
    {
        return $this->open_to_work;
    }

    public function setOpenToWork(?bool $open_to_work): static
    {
        $this->open_to_work = $open_to_work;

        return $this;
    }

    public function getLinkedinLink(): ?string
    {
        return $this->linkedin_link;
    }

    public function setLinkedinLink(?string $linkedin_link): static
    {
        $this->linkedin_link = $linkedin_link;

        return $this;
    }

    public function getPortfolioLink(): ?string
    {
        return $this->portfolio_link;
    }

    public function setPortfolioLink(?string $portfolio_link): static
    {
        $this->portfolio_link = $portfolio_link;

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

    public function getCareerStatus(): ?ResumeStatusEnum
    {
        return $this->careerStatus;
    }

    public function setCareerStatus(?ResumeStatusEnum $careerStatus): static
    {
        $this->careerStatus = $careerStatus;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): static
    {
        $this->middleName = $middleName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getCareerStatusText(): ?string
    {
        return match ($this->careerStatus)
        {
            'employed' => 'Employed',
            'open_to_work' => 'Open to work',
            'self_employed' => 'Self-Employed',
            default => null,
        };
    }

    public function getContactType(): ?string
    {
        return $this->contact_type;
    }

    public function setContactType(?string $contact_type): static
    {
        $this->contact_type = $contact_type;

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

    public function getLocationString()
    {
        $location = [];

        if($this->getCountry()) array_push($location, $this->getCountry());
        if($this->getCity()) array_push($location, $this->getCity());
        if($this->getState()) array_push($location, $this->getState());
        if($this->getZipCode()) array_push($location, $this->getZipCode());
        if($this->getZipCode()) array_push($location, $this->getZipCode());
        if($this->getLocation()) array_push($location, $this->getLocation());

        return implode(', ', $location);
    }

   


   
}
