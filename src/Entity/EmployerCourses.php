<?php

namespace App\Entity;

use Carbon\Carbon;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Repository\EmployerCoursesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EmployerCoursesRepository::class)]
#[ORM\HasLifecycleCallbacks]
class EmployerCourses
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $qualification = null;

    #[ORM\Column(length: 255)]
    private ?string $course_duration = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $close_date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $external_link = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'employerCourses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $employer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\ManyToOne(inversedBy: 'courses')]
    private ?MetierCountry $country = null;

    #[ORM\ManyToOne(inversedBy: 'employerCourses')]
    private ?MetierCity $city = null;

    #[ORM\ManyToOne(inversedBy: 'courses')]
    private ?MetierState $states = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zip = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_deleted = null;

    /**
     * @var Collection<int, CourseApplication>
     */
    #[ORM\OneToMany(targetEntity: CourseApplication::class, mappedBy: 'course', orphanRemoval: true)]
    private Collection $courseApplications;

    public function __construct()
    {
        $this->courseApplications = new ArrayCollection();
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

    public function getQualification(): ?string
    {
        return $this->qualification;
    }

    public function setQualification(string $qualification): static
    {
        $this->qualification = $qualification;

        return $this;
    }

    public function getCourseDuration(): ?string
    {
        return $this->course_duration;
    }

    public function setCourseDuration(string $course_duration): static
    {
        $this->course_duration = $course_duration;

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

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(?string $attachment): static
    {
        $this->attachment = $attachment;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(\DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getCloseDate(): ?\DateTimeInterface
    {
        return $this->close_date;
    }

    public function setCloseDate(\DateTimeInterface $close_date): static
    {
        $this->close_date = $close_date;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;

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
     * @return Collection<int, CourseApplication>
     */
    public function getCourseApplications(): Collection
    {
        return $this->courseApplications;
    }

    public function addCourseApplication(CourseApplication $courseApplication): static
    {
        if (!$this->courseApplications->contains($courseApplication)) {
            $this->courseApplications->add($courseApplication);
            $courseApplication->setCourse($this);
        }

        return $this;
    }

    public function removeCourseApplication(CourseApplication $courseApplication): static
    {
        if ($this->courseApplications->removeElement($courseApplication)) {
            // set the owning side to null (unless already changed)
            if ($courseApplication->getCourse() === $this) {
                $courseApplication->setCourse(null);
            }
        }

        return $this;
    }

    public function getPostedAt()
    {
        // dd(Carbon::parse($this->getCreatedAt())->diffForHumans());
        return Carbon::parse($this->getCreatedAt())->diffForHumans();
    }

    public function getExpiresAt()
    {

        $createdAt = Carbon::parse($this->getCreatedAt());
        $closingDate = Carbon::parse($this->getCloseDate());

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
    }


}
