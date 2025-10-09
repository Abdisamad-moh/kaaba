<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\MetierCityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MetierCityRepository::class)]
class MetierCity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['job_list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;
    #[Groups(['job_list'])]

    #[ORM\ManyToOne(inversedBy: 'metierCities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierState $state = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $state_code = null;

    #[ORM\ManyToOne(inversedBy: 'metierCities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierCountry $country = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $country_code = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wikiDataId = null;

    #[ORM\OneToOne(mappedBy: 'city', cascade: ['persist', 'remove'])]
    private ?JobseekerDetails $jobseekerDetails = null;

    /**
     * @var Collection<int, EmployerJobs>
     */
    #[ORM\OneToMany(targetEntity: EmployerJobs::class, mappedBy: 'city')]
    private Collection $employerJobs;

    /**
     * @var Collection<int, JobApplicationInterview>
     */
    #[ORM\OneToMany(targetEntity: JobApplicationInterview::class, mappedBy: 'city')]
    private Collection $jobApplicationInterviews;

    /**
     * @var Collection<int, JobSeekerEducation>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerEducation::class, mappedBy: 'city')]
    private Collection $jobSeekerEducation;

    /**
     * @var Collection<int, EmployerTender>
     */
    #[ORM\OneToMany(targetEntity: EmployerTender::class, mappedBy: 'city')]
    private Collection $tenders;

    /**
     * @var Collection<int, EmployerCourses>
     */
    #[ORM\OneToMany(targetEntity: EmployerCourses::class, mappedBy: 'city')]
    private Collection $employerCourses;

    /**
     * @var Collection<int, JobSeekerExperience>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerExperience::class, mappedBy: 'city')]
    private Collection $jobSeekerExperiences;

    /**
     * @var Collection<int, EmployerDetails>
     */
    #[ORM\OneToMany(targetEntity: EmployerDetails::class, mappedBy: 'city')]
    private Collection $companies;

    /**
     * @var Collection<int, JobSeekerCertificate>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerCertificate::class, mappedBy: 'city')]
    private Collection $jobSeekerCertificates;

    public function __construct()
    {
        $this->employerJobs = new ArrayCollection();
        $this->jobApplicationInterviews = new ArrayCollection();
        $this->jobSeekerEducation = new ArrayCollection();
        $this->tenders = new ArrayCollection();
        $this->employerCourses = new ArrayCollection();
        $this->jobSeekerExperiences = new ArrayCollection();
        $this->companies = new ArrayCollection();
        $this->jobSeekerCertificates = new ArrayCollection();
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

    public function getState(): ?MetierState
    {
        return $this->state;
    }

    public function setState(?MetierState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getStateCode(): ?string
    {
        return $this->state_code;
    }

    public function setStateCode(?string $state_code): static
    {
        $this->state_code = $state_code;

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

    public function getJobseekerDetails(): ?JobseekerDetails
    {
        return $this->jobseekerDetails;
    }

    public function setJobseekerDetails(?JobseekerDetails $jobseekerDetails): static
    {
        // unset the owning side of the relation if necessary
        if ($jobseekerDetails === null && $this->jobseekerDetails !== null) {
            $this->jobseekerDetails->setCity(null);
        }

        // set the owning side of the relation if necessary
        if ($jobseekerDetails !== null && $jobseekerDetails->getCity() !== $this) {
            $jobseekerDetails->setCity($this);
        }

        $this->jobseekerDetails = $jobseekerDetails;

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
            $employerJob->setCity($this);
        }

        return $this;
    }

    public function removeEmployerJob(EmployerJobs $employerJob): static
    {
        if ($this->employerJobs->removeElement($employerJob)) {
            // set the owning side to null (unless already changed)
            if ($employerJob->getCity() === $this) {
                $employerJob->setCity(null);
            }
        }

        return $this;

    }

    /**
     * @return Collection<int, JobApplicationInterview>
     */
    public function getJobApplicationInterviews(): Collection
    {
        return $this->jobApplicationInterviews;
    }

    public function addJobApplicationInterview(JobApplicationInterview $jobApplicationInterview): static
    {
        if (!$this->jobApplicationInterviews->contains($jobApplicationInterview)) {
            $this->jobApplicationInterviews->add($jobApplicationInterview);
            $jobApplicationInterview->setCity($this);
        }

        return $this;
    }

    public function removeJobApplicationInterview(JobApplicationInterview $jobApplicationInterview): static
    {
        if ($this->jobApplicationInterviews->removeElement($jobApplicationInterview)) {
            // set the owning side to null (unless already changed)
            if ($jobApplicationInterview->getCity() === $this) {
                $jobApplicationInterview->setCity(null);
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
            $jobSeekerEducation->setCity($this);
        }

        return $this;
    }

    public function removeJobSeekerEducation(JobSeekerEducation $jobSeekerEducation): static
    {
        if ($this->jobSeekerEducation->removeElement($jobSeekerEducation)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerEducation->getCity() === $this) {
                $jobSeekerEducation->setCity(null);
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
            $tender->setCity($this);
        }

        return $this;
    }

    public function removeTender(EmployerTender $tender): static
    {
        if ($this->tenders->removeElement($tender)) {
            // set the owning side to null (unless already changed)
            if ($tender->getCity() === $this) {
                $tender->setCity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EmployerCourses>
     */
    public function getEmployerCourses(): Collection
    {
        return $this->employerCourses;
    }

    public function addEmployerCourse(EmployerCourses $employerCourse): static
    {
        if (!$this->employerCourses->contains($employerCourse)) {
            $this->employerCourses->add($employerCourse);
            $employerCourse->setCity($this);
        }

        return $this;
    }

    public function removeEmployerCourse(EmployerCourses $employerCourse): static
    {
        if ($this->employerCourses->removeElement($employerCourse)) {
            // set the owning side to null (unless already changed)
            if ($employerCourse->getCity() === $this) {
                $employerCourse->setCity(null);
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
            $jobSeekerExperience->setCity($this);
        }

        return $this;
    }

    public function removeJobSeekerExperience(JobSeekerExperience $jobSeekerExperience): static
    {
        if ($this->jobSeekerExperiences->removeElement($jobSeekerExperience)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerExperience->getCity() === $this) {
                $jobSeekerExperience->setCity(null);
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
            $company->setCity($this);
        }

        return $this;
    }

    public function removeCompany(EmployerDetails $company): static
    {
        if ($this->companies->removeElement($company)) {
            // set the owning side to null (unless already changed)
            if ($company->getCity() === $this) {
                $company->setCity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerCertificate>
     */
    public function getJobSeekerCertificates(): Collection
    {
        return $this->jobSeekerCertificates;
    }

    public function addJobSeekerCertificate(JobSeekerCertificate $jobSeekerCertificate): static
    {
        if (!$this->jobSeekerCertificates->contains($jobSeekerCertificate)) {
            $this->jobSeekerCertificates->add($jobSeekerCertificate);
            $jobSeekerCertificate->setCity($this);
        }

        return $this;
    }

    public function removeJobSeekerCertificate(JobSeekerCertificate $jobSeekerCertificate): static
    {
        if ($this->jobSeekerCertificates->removeElement($jobSeekerCertificate)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerCertificate->getCity() === $this) {
                $jobSeekerCertificate->setCity(null);
            }
        }

        return $this;
    }
}
