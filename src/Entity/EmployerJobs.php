<?php

namespace App\Entity;

use Carbon\Carbon;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\EmployerJobsRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EmployerJobsRepository::class)]
#[HasLifecycleCallbacks]
class EmployerJobs
{

    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

        /**
     * @var Collection<int, MetierSkills>
     */
    #[ORM\ManyToMany(targetEntity: MetierSkills::class, inversedBy: 'employerJobs')]
    private Collection $required_skill;

    #[ORM\ManyToOne(inversedBy: 'employerJobs')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['job_list'])]
    private ?MetierCareers $jobtitle = null;

    #[ORM\ManyToOne(inversedBy: 'employerJobs')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['job_list'])]
    private ?MetierJobCategory $job_category = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['job_list'])]
    private ?int $number_position = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['job_list'])]
    private ?string $work_type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['job_list'])]
    private ?string $job_description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['job_list'])]
    private ?string $maximum_pay = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['job_list'])]
    private ?string $minimum_pay = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mentioned_amount_by = null;



    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\ManyToOne(inversedBy: 'employerJobs')]
    #[Groups(['job_list'])]
    private ?MetierCity $city = null;

    #[ORM\ManyToOne(inversedBy: 'employerJobs')]
    #[Groups(['job_list'])]
    private ?MetierCountry $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $education_required = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $certification_required = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $other_requirements = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['job_list'])]
    private ?\DateTimeInterface $application_closing_date = null;

    #[ORM\ManyToOne(inversedBy: 'employerJobs')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['job_list'])]
    private ?User $employer = null;

    #[ORM\Column(nullable: true)]
    private ?string $tenderDuration = null;

    #[ORM\Column(length: 255)]
    private ?string $operation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $scope = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tenderExperienceSkills = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tenderTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactPhone = null;

    /**
     * @var Collection<int, MetierJobType>
     */
    #[ORM\ManyToMany(targetEntity: MetierJobType::class, inversedBy: 'employerJobs')]
    private Collection $jobTypes;



    /**
     * @var Collection<int, MetierBenefit>
     */
    #[ORM\ManyToMany(targetEntity: MetierBenefit::class, inversedBy: 'employerJobs')]
    private Collection $benefits;

    /**
     * @var Collection<int, MetierWorkShift>
     */
    #[ORM\ManyToMany(targetEntity: MetierWorkShift::class, inversedBy: 'employerJobs')]
    private Collection $shift;

    #[ORM\ManyToOne(inversedBy: 'employerJobs')]
    private ?MetierJobIndustry $industry = null;

    #[ORM\Column(nullable: true)]
    private ?bool $show_salary = null;

    #[ORM\Column(length: 600, nullable: true)]
    private ?string $external_link = null;





    #[ORM\Column(length: 255, nullable: true)]
    private ?string $education = null;

    /**
     * @var Collection<int, MetierSkills>
     */
    #[ORM\ManyToMany(targetEntity: MetierSkills::class, inversedBy: 'empJobs')]
    #[ORM\JoinTable(name: 'employer_jobs_skills_preferred')]
    private Collection $preferred_skill;

    #[ORM\ManyToOne(inversedBy: 'employerJobs')]
    private ?MetierCurrency $currency = null;

    /**
     * @var Collection<int, EmployerJobQuestion>
     */
    #[ORM\OneToMany(targetEntity: EmployerJobQuestion::class, mappedBy: 'job', cascade: ['persist'])]
    private Collection $employerJobQuestions;
    /*
     * @var Collection<int, JobApplication>
     */
    #[ORM\OneToMany(targetEntity: JobApplication::class, mappedBy: 'job', orphanRemoval: true)]
    private Collection $jobApplications;

    #[ORM\Column(nullable: true)]
    private ?bool $immediate_hiring = null;

    #[ORM\Column(nullable: true)]
    private ?bool $locals_only = null;

    #[ORM\ManyToOne(inversedBy: 'employerJobs')]
    private ?MetierState $states = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zipe_code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $experience = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hours = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $certifications = null;

    /**
     * @var Collection<int, JobSeekerSavedJob>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerSavedJob::class, mappedBy: 'job', orphanRemoval: true)]
    private Collection $jobSeekerSavedJobs;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;

    /**
     * @var Collection<int, JobReport>
     */
    #[ORM\OneToMany(targetEntity: JobReport::class, mappedBy: 'job', orphanRemoval: true)]
    private Collection $jobReports;

    #[ORM\Column(nullable: true)]
    private ?bool $has_commission = null;

    /**
     * @var Collection<int, JobSeekerRecommendedJobs>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerRecommendedJobs::class, mappedBy: 'job', orphanRemoval: true)]
    private Collection $jobSeekerRecommendedJobs;

    #[ORM\Column(nullable: true)]
    private ?bool $expired = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_repost = null;

   

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $education_titles = null;

    #[ORM\Column(nullable: true)]
    private ?bool $requireCoverLetter = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_private = null;


    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->jobTypes = new ArrayCollection();
        $this->benefits = new ArrayCollection();
        $this->shift = new ArrayCollection();
        $this->required_skill = new ArrayCollection();
        $this->preferred_skill = new ArrayCollection();
        $this->employerJobQuestions = new ArrayCollection();

        $this->jobApplications = new ArrayCollection();
        $this->jobSeekerSavedJobs = new ArrayCollection();
        $this->jobReports = new ArrayCollection();
        $this->jobSeekerRecommendedJobs = new ArrayCollection();
    }




    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobtitle(): ?MetierCareers
    {
        return $this->jobtitle;
    }

    public function setJobtitle(?MetierCareers $jobtitle): static
    {
        $this->jobtitle = $jobtitle;

        return $this;
    }

    public function getJobCategory(): ?MetierJobCategory
    {
        return $this->job_category;
    }

    public function setJobCategory(?MetierJobCategory $job_category): static
    {
        $this->job_category = $job_category;

        return $this;
    }

    public function getNumberPosition(): ?int
    {
        return $this->number_position;
    }

    public function setNumberPosition(int $number_position): static
    {
        $this->number_position = $number_position;

        return $this;
    }

    public function getWorkType(): ?string
    {
        return $this->work_type;
    }

    public function setWorkType(string $work_type): static
    {
        $this->work_type = $work_type;

        return $this;
    }

    public function getJobDescription(): ?string
    {
        return $this->job_description;
    }

    public function setJobDescription(string $job_description): static
    {
        $this->job_description = $job_description;

        return $this;
    }

    public function getMaximumPay(): ?string
    {
        return $this->maximum_pay;
    }

    public function setMaximumPay(?string $maximum_pay): static
    {
        $this->maximum_pay = $maximum_pay;

        return $this;
    }

    public function getMinimumPay(): ?string
    {
        return $this->minimum_pay;
    }

    public function setMinimumPay(?string $minimum_pay): static
    {
        $this->minimum_pay = $minimum_pay;

        return $this;
    }

    public function getMentionedAmountBy(): ?string
    {
        return $this->mentioned_amount_by;
    }

    public function setMentionedAmountBy(?string $mentioned_amount_by): static
    {
        $this->mentioned_amount_by = $mentioned_amount_by;

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

    public function getEducationRequired(): ?string
    {
        return $this->education_required;
    }

    public function setEducationRequired(?string $education_required): static
    {
        $this->education_required = $education_required;

        return $this;
    }

    public function getCertificationRequired(): ?string
    {
        return $this->certification_required;
    }

    public function setCertificationRequired(?string $certification_required): static
    {
        $this->certification_required = $certification_required;

        return $this;
    }

    public function getOtherRequirements(): ?string
    {
        return $this->other_requirements;
    }

    public function setOtherRequirements(?string $other_requirements): static
    {
        $this->other_requirements = $other_requirements;

        return $this;
    }

    public function getApplicationClosingDate(): ?\DateTimeInterface
    {
        return $this->application_closing_date;
    }

    // public function setApplicationClosingDate(\DateTimeInterface $application_closing_date): static
    // {
    //     $this->application_closing_date = $application_closing_date;

    //     return $this;
    // }
    public function setApplicationClosingDate(?\DateTimeInterface $application_closing_date): self
    {
        $createdAt = $this->getCreatedAt();
        if ($createdAt && $application_closing_date) {
            $oneMonthLater = (clone $createdAt)->modify('+1 month');

            if ($application_closing_date > $oneMonthLater) {
                $this->application_closing_date = $oneMonthLater;
            } else {
                $this->application_closing_date = $application_closing_date;
            }
        } else {
            $this->application_closing_date = $application_closing_date;
        }

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function checkApplicationClosingDate(): void
    {
        $this->setApplicationClosingDate($this->application_closing_date);
    }
    public function updateStatusIfNeeded()
    {
        $today = new \DateTime();
        if ($this->getApplicationClosingDate() == $today && $this->getStatus() !== 'closed') {
            $this->setStatus('closed');
        }
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

    public function getTenderDuration(): ?string
    {
        return $this->tenderDuration;
    }

    public function setTenderDuration(string $tenderDuration): static
    {
        $this->tenderDuration = $tenderDuration;

        return $this;
    }

    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public function setOperation(string $operation): static
    {
        $this->operation = $operation;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function getTenderExperienceSkills(): ?string
    {
        return $this->tenderExperienceSkills;
    }

    public function setTenderExperienceSkills(?string $tenderExperienceSkills): static
    {
        $this->tenderExperienceSkills = $tenderExperienceSkills;

        return $this;
    }

    public function getTenderTitle(): ?string
    {
        return $this->tenderTitle;
    }

    public function setTenderTitle(string $tenderTitle): static
    {
        $this->tenderTitle = $tenderTitle;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): static
    {
        $this->contactName = $contactName;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): static
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    /**
     * @return Collection<int, MetierJobType>
     */
    public function getJobTypes(): Collection
    {
        return $this->jobTypes;
    }

    public function addJobType(MetierJobType $jobType): static
    {
        if (!$this->jobTypes->contains($jobType)) {
            $this->jobTypes->add($jobType);
        }

        return $this;
    }

    public function removeJobType(MetierJobType $jobType): static
    {
        $this->jobTypes->removeElement($jobType);

        return $this;
    }

    public function getPostedAt()
    {
        // dd(Carbon::parse($this->getCreatedAt())->diffForHumans());
        return Carbon::parse($this->getCreatedAt())->diffForHumans();
    }

    public function getExpiresAt()
    {

        // Create Carbon instances
        $createdAt = Carbon::parse($this->getCreatedAt());
        $closingDate = Carbon::parse($this->getApplicationClosingDate());

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



    /**
     * @return Collection<int, MetierBenefit>
     */
    public function getBenefits(): Collection
    {
        return $this->benefits;
    }

    public function addBenefit(MetierBenefit $benefit): static
    {
        if (!$this->benefits->contains($benefit)) {
            $this->benefits->add($benefit);
        }

        return $this;
    }

    public function removeBenefit(MetierBenefit $benefit): static
    {
        $this->benefits->removeElement($benefit);

        return $this;
    }

    /**
     * @return Collection<int, MetierWorkShift>
     */
    public function getShift(): Collection
    {
        return $this->shift;
    }

    public function addShift(MetierWorkShift $shift): static
    {
        if (!$this->shift->contains($shift)) {
            $this->shift->add($shift);
        }

        return $this;
    }

    public function removeShift(MetierWorkShift $shift): static
    {
        $this->shift->removeElement($shift);

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

    public function isShowSalary(): ?bool
    {
        return $this->show_salary;
    }

    public function setShowSalary(?bool $show_salary): static
    {
        $this->show_salary = $show_salary;

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

    /**
     * @return Collection<int, MetierSkills>
     */
    public function getRequiredSkill(): Collection
    {
        return $this->required_skill;
    }

    public function addRequiredSkill(MetierSkills $requiredSkill): static
    {
        if (!$this->required_skill->contains($requiredSkill)) {
            $this->required_skill->add($requiredSkill);
        }

        return $this;
    }

    public function removeRequiredSkill(MetierSkills $requiredSkill): static
    {
        $this->required_skill->removeElement($requiredSkill);

        return $this;
    }



    public function getEducation(): ?string
    {
        return $this->education;
    }

    public function setEducation(?string $education): static
    {
        $this->education = $education;

        return $this;
    }

    /**
     * @return Collection<int, MetierSkills>
     */
    public function getPreferredSkill(): Collection
    {
        return $this->preferred_skill;
    }

    public function addPreferredSkill(MetierSkills $preferredSkill): static
    {
        if (!$this->preferred_skill->contains($preferredSkill)) {
            $this->preferred_skill->add($preferredSkill);
        }

        return $this;
    }

    public function removePreferredSkill(MetierSkills $preferredSkill): static
    {
        $this->preferred_skill->removeElement($preferredSkill);

        return $this;
    }

    public function getCurrency(): ?MetierCurrency
    {
        return $this->currency;
    }

    public function setCurrency(?MetierCurrency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return Collection<int, EmployerJobQuestion>
     */
    public function getEmployerJobQuestions(): Collection
    {
        return $this->employerJobQuestions;
    }

    public function addEmployerJobQuestion(EmployerJobQuestion $employerJobQuestion): static
    {
        if (!$this->employerJobQuestions->contains($employerJobQuestion)) {
            $this->employerJobQuestions->add($employerJobQuestion);
            $employerJobQuestion->setJob($this);
        }

        return $this;
    }

    /*
     * @return Collection<int, JobApplication>
     */
    public function getJobApplications(): Collection
    {
        return $this->jobApplications;
    }

    public function addJobApplication(JobApplication $jobApplication): static
    {
        if (!$this->jobApplications->contains($jobApplication)) {
            $this->jobApplications->add($jobApplication);
            $jobApplication->setJob($this);
        }

        return $this;
    }

    public function removeEmployerJobQuestion(EmployerJobQuestion $employerJobQuestion): static
    {
        if ($this->employerJobQuestions->removeElement($employerJobQuestion)) {
            // set the owning side to null (unless already changed)
            if ($employerJobQuestion->getJob() === $this) {
                $employerJobQuestion->setJob(null);
            }
        }

        return $this;
    }

    public function removeJobApplication(JobApplication $jobApplication): static
    {
        if ($this->jobApplications->removeElement($jobApplication)) {
            // set the owning side to null (unless already changed)
            if ($jobApplication->getJob() === $this) {
                $jobApplication->setJob(null);
            }
        }

        return $this;
    }

    public function isImmediateHiring(): ?bool
    {
        return $this->immediate_hiring;
    }

    public function setImmediateHiring(?bool $immediate_hiring): static
    {
        $this->immediate_hiring = $immediate_hiring;

        return $this;
    }

    public function isLocalsOnly(): ?bool
    {
        return $this->locals_only;
    }

    public function setLocalsOnly(?bool $locals_only): static
    {
        $this->locals_only = $locals_only;

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

    public function getZipeCode(): ?string
    {
        return $this->zipe_code;
    }

    public function setZipeCode(?string $zipe_code): static
    {
        $this->zipe_code = $zipe_code;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

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

    public function getHours(): ?string
    {
        return $this->hours;
    }

    public function setHours(?string $hours): static
    {
        $this->hours = $hours;

        return $this;
    }

    public function getCertifications(): ?string
    {
        return $this->certifications;
    }

    public function setCertifications(?string $certifications): static
    {
        $this->certifications = $certifications;

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerSavedJob>
     */
    public function getJobSeekerSavedJobs(): Collection
    {
        return $this->jobSeekerSavedJobs;
    }

    public function addJobSeekerSavedJob(JobSeekerSavedJob $jobSeekerSavedJob): static
    {
        if (!$this->jobSeekerSavedJobs->contains($jobSeekerSavedJob)) {
            $this->jobSeekerSavedJobs->add($jobSeekerSavedJob);
            $jobSeekerSavedJob->setJob($this);
        }

        return $this;
    }

    public function removeJobSeekerSavedJob(JobSeekerSavedJob $jobSeekerSavedJob): static
    {
        if ($this->jobSeekerSavedJobs->removeElement($jobSeekerSavedJob)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerSavedJob->getJob() === $this) {
                $jobSeekerSavedJob->setJob(null);
            }
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
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

    /**
     * @return Collection<int, JobReport>
     */
    public function getJobReports(): Collection
    {
        return $this->jobReports;
    }

    public function addJobReport(JobReport $jobReport): static
    {
        if (!$this->jobReports->contains($jobReport)) {
            $this->jobReports->add($jobReport);
            $jobReport->setJob($this);
        }

        return $this;
    }

    public function removeJobReport(JobReport $jobReport): static
    {
        if ($this->jobReports->removeElement($jobReport)) {
            // set the owning side to null (unless already changed)
            if ($jobReport->getJob() === $this) {
                $jobReport->setJob(null);
            }
        }

        return $this;
    }

    public function hasCommission(): ?bool
    {
        return $this->has_commission;
    }

    public function setHasCommission(?bool $has_commission): static
    {
        $this->has_commission = $has_commission;

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerRecommendedJobs>
     */
    public function getJobSeekerRecommendedJobs(): Collection
    {
        return $this->jobSeekerRecommendedJobs;
    }

    public function addJobSeekerRecommendedJob(JobSeekerRecommendedJobs $jobSeekerRecommendedJob): static
    {
        if (!$this->jobSeekerRecommendedJobs->contains($jobSeekerRecommendedJob)) {
            $this->jobSeekerRecommendedJobs->add($jobSeekerRecommendedJob);
            $jobSeekerRecommendedJob->setJob($this);
        }

        return $this;
    }

    public function removeJobSeekerRecommendedJob(JobSeekerRecommendedJobs $jobSeekerRecommendedJob): static
    {
        if ($this->jobSeekerRecommendedJobs->removeElement($jobSeekerRecommendedJob)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerRecommendedJob->getJob() === $this) {
                $jobSeekerRecommendedJob->setJob(null);
            }
        }

        return $this;
    }

    public function isExpired(): ?bool
    {
        return $this->expired;
    }

    public function setExpired(?bool $expired): static
    {
        $this->expired = $expired;

        return $this;
    }

    public function isRepost(): ?bool
    {
        return $this->is_repost;
    }

    public function setRepost(?bool $is_repost): static
    {
        $this->is_repost = $is_repost;

        return $this;
    }

 
    public function getEducationTitles(): ?string
    {
        return $this->education_titles;
    }


    public function setEducationTitles(?string $education_titles): static
    {
        $this->education_titles = $education_titles;

        return $this;
    }

    public function isRequireCoverLetter(): ?bool
    {
        return $this->requireCoverLetter;
    }

    public function setRequireCoverLetter(?bool $requireCoverLetter): static
    {
        $this->requireCoverLetter = $requireCoverLetter;

        return $this;
    }

    public function getFormattedLocation()
    {
        $city = $this->getCity()?->getName();
        $state = $this->getStates()?->getName();
        $country = $this->getCountry()?->getName();
        // dd($state);

        // make them an array, and then into comma seperated string
        $location = [$city, $state, $country];
        $location = array_filter($location, function ($value) {
            return $value !== null;
        });
        $location = implode(', ', $location);

        return $location;
    }

    public function getIsPrivate(): ?bool
    {
        return $this->is_private;
    }

    public function setIsPrivate(?bool $is_private): static
    {
        $this->is_private = $is_private;
        return $this;
    }

}