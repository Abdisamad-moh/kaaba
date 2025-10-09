<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\MetierCountryRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MetierCountryRepository::class)]
class MetierCountry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['job_list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['job_list'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $iso3 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeric_code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $iso2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phonecode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $capital = null;

    #[ORM\Column(length: 255)]
    private ?string $currency = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currency_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currency_symbol = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tld = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $native = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $region = null;

    #[ORM\ManyToOne(inversedBy: 'metierCountries')]
    private ?MetierRegion $region_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nationality = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $timezones = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $translations = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emoji = null;

    #[ORM\Column(length: 191, nullable: true)]
    private ?string $emojiU = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wikiDataId = null;

    /**
     * @var Collection<int, MetierState>
     */
    #[ORM\OneToMany(targetEntity: MetierState::class, mappedBy: 'country')]
    private Collection $metierStates;

    /**
     * @var Collection<int, MetierCity>
     */
    #[ORM\OneToMany(targetEntity: MetierCity::class, mappedBy: 'country')]
    private Collection $metierCities;

    #[ORM\OneToOne(mappedBy: 'country', cascade: ['persist', 'remove'])]
    private ?JobseekerDetails $jobseekerDetails = null;

    /**
     * @var Collection<int, EmployerJobs>
     */
    #[ORM\OneToMany(targetEntity: EmployerJobs::class, mappedBy: 'country')]
    private Collection $employerJobs;

    /**
     * @var Collection<int, JobApplicationInterview>
     */
    #[ORM\OneToMany(targetEntity: JobApplicationInterview::class, mappedBy: 'country')]
    private Collection $jobApplicationInterviews;

    /**
     * @var Collection<int, JobSeekerEducation>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerEducation::class, mappedBy: 'country')]
    private Collection $jobSeekerEducation;

    /**
     * @var Collection<int, EmployerTender>
     */
    #[ORM\OneToMany(targetEntity: EmployerTender::class, mappedBy: 'country')]
    private Collection $employerTenders;

    /**
     * @var Collection<int, EmployerCourses>
     */
    #[ORM\OneToMany(targetEntity: EmployerCourses::class, mappedBy: 'country')]
    private Collection $courses;

    /**
     * @var Collection<int, JobSeekerExperience>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerExperience::class, mappedBy: 'country')]
    private Collection $jobSeekerExperiences;

    /**
     * @var Collection<int, EmployerDetails>
     */
    #[ORM\OneToMany(targetEntity: EmployerDetails::class, mappedBy: 'country')]
    private Collection $companies;

    /**
     * @var Collection<int, TenderApplication>
     */
    #[ORM\OneToMany(targetEntity: TenderApplication::class, mappedBy: 'country')]
    private Collection $tenderApplications;

    /**
     * @var Collection<int, JobSeekerCertificate>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerCertificate::class, mappedBy: 'country')]
    private Collection $jobSeekerCertificates;

    /**
     * @var Collection<int, JobseekerDetails>
     */
    #[ORM\OneToMany(targetEntity: JobseekerDetails::class, mappedBy: 'country')]
    private Collection $yes;

    /**
     * @var Collection<int, MetierInquiry>
     */
    #[ORM\OneToMany(targetEntity: MetierInquiry::class, mappedBy: 'country')]
    private Collection $metierInquiries;

    public function __construct()
    {
        $this->metierStates = new ArrayCollection();
        $this->metierCities = new ArrayCollection();
        $this->employerJobs = new ArrayCollection();
        $this->jobApplicationInterviews = new ArrayCollection();
        $this->jobSeekerEducation = new ArrayCollection();
        $this->employerTenders = new ArrayCollection();
        $this->courses = new ArrayCollection();
        $this->jobSeekerExperiences = new ArrayCollection();
        $this->companies = new ArrayCollection();
        $this->tenderApplications = new ArrayCollection();
        $this->jobSeekerCertificates = new ArrayCollection();
        $this->yes = new ArrayCollection();
        $this->metierInquiries = new ArrayCollection();
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

    public function getIso3(): ?string
    {
        return $this->iso3;
    }

    public function setIso3(?string $iso3): static
    {
        $this->iso3 = $iso3;

        return $this;
    }

    public function getNumericCode(): ?string
    {
        return $this->numeric_code;
    }

    public function setNumericCode(?string $numeric_code): static
    {
        $this->numeric_code = $numeric_code;

        return $this;
    }

    public function getIso2(): ?string
    {
        return $this->iso2;
    }

    public function setIso2(?string $iso2): static
    {
        $this->iso2 = $iso2;

        return $this;
    }

    public function getPhonecode(): ?string
    {
        return $this->phonecode;
    }

    public function setPhonecode(?string $phonecode): static
    {
        $this->phonecode = $phonecode;

        return $this;
    }

    public function getCapital(): ?string
    {
        return $this->capital;
    }

    public function setCapital(?string $capital): static
    {
        $this->capital = $capital;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrencyName(): ?string
    {
        return $this->currency_name;
    }

    public function setCurrencyName(?string $currency_name): static
    {
        $this->currency_name = $currency_name;

        return $this;
    }

    public function getCurrencySymbol(): ?string
    {
        return $this->currency_symbol;
    }

    public function setCurrencySymbol(?string $currency_symbol): static
    {
        $this->currency_symbol = $currency_symbol;

        return $this;
    }

    public function getTld(): ?string
    {
        return $this->tld;
    }

    public function setTld(?string $tld): static
    {
        $this->tld = $tld;

        return $this;
    }

    public function getNative(): ?string
    {
        return $this->native;
    }

    public function setNative(?string $native): static
    {
        $this->native = $native;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getRegionId(): ?MetierRegion
    {
        return $this->region_id;
    }

    public function setRegionId(?MetierRegion $region_id): static
    {
        $this->region_id = $region_id;

        return $this;
    }

 
    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    public function setNationality(?string $nationality): static
    {
        $this->nationality = $nationality;

        return $this;
    }

    public function getTimezones(): ?string
    {
        return $this->timezones;
    }

    public function setTimezones(?string $timezones): static
    {
        $this->timezones = $timezones;

        return $this;
    }

    public function getTranslations(): ?string
    {
        return $this->translations;
    }

    public function setTranslations(?string $translations): static
    {
        $this->translations = $translations;

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

    public function getEmoji(): ?string
    {
        return $this->emoji;
    }

    public function setEmoji(?string $emoji): static
    {
        $this->emoji = $emoji;

        return $this;
    }

    public function getEmojiU(): ?string
    {
        return $this->emojiU;
    }

    public function setEmojiU(?string $emojiU): static
    {
        $this->emojiU = $emojiU;

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
     * @return Collection<int, MetierState>
     */
    public function getMetierStates(): Collection
    {
        return $this->metierStates;
    }

    public function addMetierState(MetierState $metierState): static
    {
        if (!$this->metierStates->contains($metierState)) {
            $this->metierStates->add($metierState);
            $metierState->setCountry($this);
        }

        return $this;
    }

    public function removeMetierState(MetierState $metierState): static
    {
        if ($this->metierStates->removeElement($metierState)) {
            // set the owning side to null (unless already changed)
            if ($metierState->getCountry() === $this) {
                $metierState->setCountry(null);
            }
        }

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
            $metierCity->setCountry($this);
        }

        return $this;
    }

    public function removeMetierCity(MetierCity $metierCity): static
    {
        if ($this->metierCities->removeElement($metierCity)) {
            // set the owning side to null (unless already changed)
            if ($metierCity->getCountry() === $this) {
                $metierCity->setCountry(null);
            }
        }

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
            $this->jobseekerDetails->setCountry(null);
        }

        // set the owning side of the relation if necessary
        if ($jobseekerDetails !== null && $jobseekerDetails->getCountry() !== $this) {
            $jobseekerDetails->setCountry($this);
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
            $employerJob->setCountry($this);
        }

        return $this;
    }

    public function removeEmployerJob(EmployerJobs $employerJob): static
    {
        if ($this->employerJobs->removeElement($employerJob)) {
            // set the owning side to null (unless already changed)
            if ($employerJob->getCountry() === $this) {
                $employerJob->setCountry(null);
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
            $jobApplicationInterview->setCountry($this);
        }

        return $this;
    }

    public function removeJobApplicationInterview(JobApplicationInterview $jobApplicationInterview): static
    {
        if ($this->jobApplicationInterviews->removeElement($jobApplicationInterview)) {
            // set the owning side to null (unless already changed)
            if ($jobApplicationInterview->getCountry() === $this) {
                $jobApplicationInterview->setCountry(null);
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
            $jobSeekerEducation->setCountry($this);
        }

        return $this;
    }

    public function removeJobSeekerEducation(JobSeekerEducation $jobSeekerEducation): static
    {
        if ($this->jobSeekerEducation->removeElement($jobSeekerEducation)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerEducation->getCountry() === $this) {
                $jobSeekerEducation->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EmployerTender>
     */
    public function getEmployerTenders(): Collection
    {
        return $this->employerTenders;
    }

    public function addEmployerTender(EmployerTender $employerTender): static
    {
        if (!$this->employerTenders->contains($employerTender)) {
            $this->employerTenders->add($employerTender);
            $employerTender->setCountry($this);
        }

        return $this;
    }

    public function removeEmployerTender(EmployerTender $employerTender): static
    {
        if ($this->employerTenders->removeElement($employerTender)) {
            // set the owning side to null (unless already changed)
            if ($employerTender->getCountry() === $this) {
                $employerTender->setCountry(null);
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
            $course->setCountry($this);
        }

        return $this;
    }

    public function removeCourse(EmployerCourses $course): static
    {
        if ($this->courses->removeElement($course)) {
            // set the owning side to null (unless already changed)
            if ($course->getCountry() === $this) {
                $course->setCountry(null);
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
            $jobSeekerExperience->setCountry($this);
        }

        return $this;
    }

    public function removeJobSeekerExperience(JobSeekerExperience $jobSeekerExperience): static
    {
        if ($this->jobSeekerExperiences->removeElement($jobSeekerExperience)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerExperience->getCountry() === $this) {
                $jobSeekerExperience->setCountry(null);
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
            $company->setCountry($this);
        }

        return $this;
    }

    public function removeCompany(EmployerDetails $company): static
    {
        if ($this->companies->removeElement($company)) {
            // set the owning side to null (unless already changed)
            if ($company->getCountry() === $this) {
                $company->setCountry(null);
            }
        }

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
            $tenderApplication->setCountry($this);
        }

        return $this;
    }

    public function removeTenderApplication(TenderApplication $tenderApplication): static
    {
        if ($this->tenderApplications->removeElement($tenderApplication)) {
            // set the owning side to null (unless already changed)
            if ($tenderApplication->getCountry() === $this) {
                $tenderApplication->setCountry(null);
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
            $jobSeekerCertificate->setCountry($this);
        }

        return $this;
    }

    public function removeJobSeekerCertificate(JobSeekerCertificate $jobSeekerCertificate): static
    {
        if ($this->jobSeekerCertificates->removeElement($jobSeekerCertificate)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerCertificate->getCountry() === $this) {
                $jobSeekerCertificate->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobseekerDetails>
     */
    public function getYes(): Collection
    {
        return $this->yes;
    }

    public function addYe(JobseekerDetails $ye): static
    {
        if (!$this->yes->contains($ye)) {
            $this->yes->add($ye);
            $ye->setCountry($this);
        }

        return $this;
    }

    public function removeYe(JobseekerDetails $ye): static
    {
        if ($this->yes->removeElement($ye)) {
            // set the owning side to null (unless already changed)
            if ($ye->getCountry() === $this) {
                $ye->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierInquiry>
     */
    public function getMetierInquiries(): Collection
    {
        return $this->metierInquiries;
    }

    public function addMetierInquiry(MetierInquiry $metierInquiry): static
    {
        if (!$this->metierInquiries->contains($metierInquiry)) {
            $this->metierInquiries->add($metierInquiry);
            $metierInquiry->setCountry($this);
        }

        return $this;
    }

    public function removeMetierInquiry(MetierInquiry $metierInquiry): static
    {
        if ($this->metierInquiries->removeElement($metierInquiry)) {
            // set the owning side to null (unless already changed)
            if ($metierInquiry->getCountry() === $this) {
                $metierInquiry->setCountry(null);
            }
        }

        return $this;
    }
}
