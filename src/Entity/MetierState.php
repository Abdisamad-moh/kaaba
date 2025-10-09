<?php

namespace App\Entity;

use App\Repository\MetierStateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierStateRepository::class)]
class MetierState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'metierStates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierCountry $country = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $country_code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fips_code = null;

    #[ORM\Column(length: 255)]
    private ?string $iso2 = null;

    #[ORM\Column(length: 191, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wikiDataId = null;

    /**
     * @var Collection<int, MetierCity>
     */
    #[ORM\OneToMany(targetEntity: MetierCity::class, mappedBy: 'state')]
    private Collection $metierCities;

    /**
     * @var Collection<int, JobseekerDetails>
     */
    #[ORM\OneToMany(targetEntity: JobseekerDetails::class, mappedBy: 'state')]
    private Collection $jobseekerDetails;

    /**
     * @var Collection<int, EmployerJobs>
     */
    #[ORM\OneToMany(targetEntity: EmployerJobs::class, mappedBy: 'states')]
    private Collection $employerJobs;

    /**
     * @var Collection<int, JobSeekerEducation>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerEducation::class, mappedBy: 'state')]
    private Collection $jobSeekerEducation;

    /**
     * @var Collection<int, EmployerTender>
     */
    #[ORM\OneToMany(targetEntity: EmployerTender::class, mappedBy: 'states')]
    private Collection $tenders;

    /**
     * @var Collection<int, EmployerCourses>
     */
    #[ORM\OneToMany(targetEntity: EmployerCourses::class, mappedBy: 'states')]
    private Collection $courses;

    /**
     * @var Collection<int, JobSeekerExperience>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerExperience::class, mappedBy: 'state')]
    private Collection $jobSeekerExperiences;

    /**
     * @var Collection<int, EmployerDetails>
     */
    #[ORM\OneToMany(targetEntity: EmployerDetails::class, mappedBy: 'state')]
    private Collection $companies;

    public function __construct()
    {
        $this->metierCities = new ArrayCollection();
        $this->jobseekerDetails = new ArrayCollection();
        $this->employerJobs = new ArrayCollection();
        $this->jobSeekerEducation = new ArrayCollection();
        $this->tenders = new ArrayCollection();
        $this->courses = new ArrayCollection();
        $this->jobSeekerExperiences = new ArrayCollection();
        $this->companies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getCountryCode(): ?string
    {
        return $this->country_code;
    }

    public function setCountryCode(?string $country_code): static
    {
        $this->country_code = $country_code;

        return $this;
    }

    public function getFipsCode(): ?string
    {
        return $this->fips_code;
    }

    public function setFipsCode(?string $fips_code): static
    {
        $this->fips_code = $fips_code;

        return $this;
    }

    public function getIso2(): ?string
    {
        return $this->iso2;
    }

    public function setIso2(string $iso2): static
    {
        $this->iso2 = $iso2;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getWikiDataId(): ?string
    {
        return $this->wikiDataId;
    }

    public function setWikiDataId(?string $wikiDataId): static
    {
        $this->wikiDataId = $wikiDataId;

        return $this;
    }

    /**
     * @return Collection<int, MetierCity>
     */
    public function getMetierCities(): Collection
    {
        return $this->metierCities;
    }

    public function addMetierCity(MetierCity $metierCity): static
    {
        if (!$this->metierCities->contains($metierCity)) {
            $this->metierCities->add($metierCity);
            $metierCity->setState($this);
        }

        return $this;
    }

    public function removeMetierCity(MetierCity $metierCity): static
    {
        if ($this->metierCities->removeElement($metierCity)) {
            // set the owning side to null (unless already changed)
            if ($metierCity->getState() === $this) {
                $metierCity->setState(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobseekerDetails>
     */
    public function getJobseekerDetails(): Collection
    {
        return $this->jobseekerDetails;
    }

    public function addJobseekerDetail(JobseekerDetails $jobseekerDetail): static
    {
        if (!$this->jobseekerDetails->contains($jobseekerDetail)) {
            $this->jobseekerDetails->add($jobseekerDetail);
            $jobseekerDetail->setState($this);
        }

        return $this;
    }

    public function removeJobseekerDetail(JobseekerDetails $jobseekerDetail): static
    {
        if ($this->jobseekerDetails->removeElement($jobseekerDetail)) {
            // set the owning side to null (unless already changed)
            if ($jobseekerDetail->getState() === $this) {
                $jobseekerDetail->setState(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @return Collection<int, EmployerJobs>
     */
    public function getEmployerJobs(): Collection
    {
        return $this->employerJobs;
    }

    public function addEmployerJob(EmployerJobs $employerJob): static
    {
        if (!$this->employerJobs->contains($employerJob)) {
            $this->employerJobs->add($employerJob);
            $employerJob->setStates($this);
        }

        return $this;
    }

    public function removeEmployerJob(EmployerJobs $employerJob): static
    {
        if ($this->employerJobs->removeElement($employerJob)) {
            // set the owning side to null (unless already changed)
            if ($employerJob->getStates() === $this) {
                $employerJob->setStates(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerEducation>
     */
    public function getJobSeekerEducation(): Collection
    {
        return $this->jobSeekerEducation;
    }

    public function addJobSeekerEducation(JobSeekerEducation $jobSeekerEducation): static
    {
        if (!$this->jobSeekerEducation->contains($jobSeekerEducation)) {
            $this->jobSeekerEducation->add($jobSeekerEducation);
            $jobSeekerEducation->setState($this);
        }

        return $this;
    }

    public function removeJobSeekerEducation(JobSeekerEducation $jobSeekerEducation): static
    {
        if ($this->jobSeekerEducation->removeElement($jobSeekerEducation)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerEducation->getState() === $this) {
                $jobSeekerEducation->setState(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EmployerTender>
     */
    public function getTenders(): Collection
    {
        return $this->tenders;
    }

    public function addTender(EmployerTender $tender): static
    {
        if (!$this->tenders->contains($tender)) {
            $this->tenders->add($tender);
            $tender->setState($this);
        }

        return $this;
    }

    public function removeTender(EmployerTender $tender): static
    {
        if ($this->tenders->removeElement($tender)) {
            // set the owning side to null (unless already changed)
            if ($tender->getState() === $this) {
                $tender->setState(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EmployerCourses>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function addCourse(EmployerCourses $course): static
    {
        if (!$this->courses->contains($course)) {
            $this->courses->add($course);
            $course->setStates($this);
        }

        return $this;
    }

    public function removeCourse(EmployerCourses $course): static
    {
        if ($this->courses->removeElement($course)) {
            // set the owning side to null (unless already changed)
            if ($course->getStates() === $this) {
                $course->setStates(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerExperience>
     */
    public function getJobSeekerExperiences(): Collection
    {
        return $this->jobSeekerExperiences;
    }

    public function addJobSeekerExperience(JobSeekerExperience $jobSeekerExperience): static
    {
        if (!$this->jobSeekerExperiences->contains($jobSeekerExperience)) {
            $this->jobSeekerExperiences->add($jobSeekerExperience);
            $jobSeekerExperience->setState($this);
        }

        return $this;
    }

    public function removeJobSeekerExperience(JobSeekerExperience $jobSeekerExperience): static
    {
        if ($this->jobSeekerExperiences->removeElement($jobSeekerExperience)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerExperience->getState() === $this) {
                $jobSeekerExperience->setState(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EmployerDetails>
     */
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    public function addCompany(EmployerDetails $company): static
    {
        if (!$this->companies->contains($company)) {
            $this->companies->add($company);
            $company->setState($this);
        }

        return $this;
    }

    public function removeCompany(EmployerDetails $company): static
    {
        if ($this->companies->removeElement($company)) {
            // set the owning side to null (unless already changed)
            if ($company->getState() === $this) {
                $company->setState(null);
            }
        }

        return $this;
    }
}
