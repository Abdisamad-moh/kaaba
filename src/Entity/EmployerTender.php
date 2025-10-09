<?php

namespace App\Entity;

use Carbon\Carbon;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Repository\EmployerTenderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EmployerTenderRepository::class)]
class EmployerTender
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $duration = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $posting_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $closing_date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $external_link = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachment = null;

    #[ORM\ManyToOne(inversedBy: 'tenders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $employer = null;

    #[ORM\Column(length: 255)]
    private ?string $company_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact_email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact_phone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $skill_and_experience = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $file = null;

    #[ORM\ManyToOne(inversedBy: 'employerTenders')]
    private ?MetierCountry $country = null;

    #[ORM\ManyToOne(inversedBy: 'tenders')]
    private ?MetierCity $city = null;

    #[ORM\ManyToOne(inversedBy: 'tenders')]
    private ?MetierState $states = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zip = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_deleted = null;

    /**
     * @var Collection<int, TenderApplication>
     */
    #[ORM\OneToMany(targetEntity: TenderApplication::class, mappedBy: 'tender', orphanRemoval: true)]
    private Collection $tenderApplications;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $funded_by = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $project_location = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $value = null;

    public function __construct()
    {
        $this->tenderApplications = new ArrayCollection();
        $this->uuid = Uuid::v4();
    }
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(string $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;

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

    public function getPostingDate(): ?\DateTimeInterface
    {
        return $this->posting_date;
    }

    public function setPostingDate(\DateTimeInterface $posting_date): static
    {
        $this->posting_date = $posting_date;

        return $this;
    }

    public function getClosingDate(): ?\DateTimeInterface
    {
        return $this->closing_date;
    }

    public function setClosingDate(\DateTimeInterface $closing_date): static
    {
        $this->closing_date = $closing_date;

        return $this;
    }

    public function getExternalLink(): ?string
    {
        return $this->external_link;
    }

    public function setExternalLink(?string $external_link): static
    {
        $this->external_link = $external_link;

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

    public function getEmployer(): ?User
    {
        return $this->employer;
    }

    public function setEmployer(?User $employer): static
    {
        $this->employer = $employer;

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

    public function getContactName(): ?string
    {
        return $this->contact_name;
    }

    public function setContactName(?string $contact_name): static
    {
        $this->contact_name = $contact_name;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contact_email;
    }

    public function setContactEmail(string $contact_email): static
    {
        $this->contact_email = $contact_email;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contact_phone;
    }

    public function setContactPhone(string $contact_phone): static
    {
        $this->contact_phone = $contact_phone;

        return $this;
    }

    public function getSkillAndExperience(): ?string
    {
        return $this->skill_and_experience;
    }

    public function setSkillAndExperience(?string $skill_and_experience): static
    {
        $this->skill_and_experience = $skill_and_experience;

        return $this;
    }

    public function isStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function getStates(): ?MetierState
    {
        return $this->states;
    }

    public function setStates(?MetierState $states): static
    {
        $this->states = $states;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): static
    {
        $this->zip = $zip;

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->is_deleted;
    }

    public function setDeleted(?bool $is_deleted): static
    {
        $this->is_deleted = $is_deleted;

        return $this;
    }

    /**
     * @return Collection<int, TenderApplication>
     */
    public function getTenderApplications(): Collection
    {
        return $this->tenderApplications;
    }

    public function addTenderApplication(TenderApplication $tenderApplication): static
    {
        if (!$this->tenderApplications->contains($tenderApplication)) {
            $this->tenderApplications->add($tenderApplication);
            $tenderApplication->setTender($this);
        }

        return $this;
    }

    public function removeTenderApplication(TenderApplication $tenderApplication): static
    {
        if ($this->tenderApplications->removeElement($tenderApplication)) {
            // set the owning side to null (unless already changed)
            if ($tenderApplication->getTender() === $this) {
                $tenderApplication->setTender(null);
            }
        }

        return $this;
    }

    public function getPostedAt()
    {
        // dd(Carbon::parse($this->getCreatedAt())->diffForHumans());
        return Carbon::parse($this->getPostingDate())->diffForHumans();
    }

    public function getExpiresAt()
    {
        // Create Carbon instances
        $createdAt = Carbon::parse($this->getPostingDate());
        $closingDate = Carbon::parse($this->getClosingDate());

        // Current time
        $now = Carbon::now();

        // Calculate one month from the posted date
        $oneMonthFromPosted = (clone $createdAt)->addMonth();

        // Use the earliest of the closing date or one month from posted (ignore 'now' for future expiration)
        $finalDate = min($closingDate, $oneMonthFromPosted);

        // Compare against 'now' to get a relevant message
        if ($finalDate > $now) {
            $expiresAt = $now->diffForHumans($finalDate, true);  // Future
        } else {
            $expiresAt = $finalDate->diffForHumans($now, true);  // Past
        }

        return $expiresAt;
        // Calculate for me the expirey date, in DateTime object, calculating from createdAt and application closing date

    }

    public function getFundedBy(): ?string
    {
        return $this->funded_by;
    }

    public function setFundedBy(?string $funded_by): static
    {
        $this->funded_by = $funded_by;

        return $this;
    }

    public function getProjectLocation(): ?string
    {
        return $this->project_location;
    }

    public function setProjectLocation(?string $project_location): static
    {
        $this->project_location = $project_location;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getLocationString()
    {
        $array = [];

        if($this->getCity()) array_push($array, $this->getCity()->getName());
        if($this->getStates()) array_push($array, $this->getStates()->getName());
        if($this->getCountry()) array_push($array, $this->getCountry()->getName());

        return $array;
    }
}
