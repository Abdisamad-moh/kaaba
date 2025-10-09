<?php

namespace App\Entity;

use Carbon\Carbon;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
#[HasLifecycleCallbacks]

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use Timestamps;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['job_list'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(['job_list'])]
    private ?string $name = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(nullable: false)]
    private ?bool $status = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $type = null;

    #[ORM\OneToOne(mappedBy: 'jobseeker', cascade: ['persist', 'remove'])]
    private ?JobseekerDetails $jobseekerDetails = null;

    #[ORM\OneToOne(mappedBy: 'employer', cascade: ['persist', 'remove'])]
    private ?EmployerDetails $employerDetails = null;

    /**
     * @var Collection<int, EmployerJobs>
     */
    #[ORM\OneToMany(targetEntity: EmployerJobs::class, mappedBy: 'employer', orphanRemoval: true)]
    private Collection $employerJobs;

    /**
     * @var Collection<int, JobSeekerExperience>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerExperience::class, mappedBy: 'jobSeeker', orphanRemoval: true)]
    private Collection $jobSeekerExperiences;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $profile = null;

    /**
     * @var Collection<int, JobSeekerWork>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerWork::class, mappedBy: 'jobSeeker', orphanRemoval: true)]
    private Collection $jobSeekerWorks;

    /**
     * @var Collection<int, JobApplication>
     */
    #[ORM\OneToMany(targetEntity: JobApplication::class, mappedBy: 'jobSeeker', orphanRemoval: true)]
    private Collection $jobApplications;

    /**
     * @var Collection<int, JobApplication>
     */
    #[ORM\OneToMany(targetEntity: JobApplication::class, mappedBy: 'employer')]
    private Collection $employerApplications;

    /**
     * @var Collection<int, JobApplicationInterview>
     */
    #[ORM\OneToMany(targetEntity: JobApplicationInterview::class, mappedBy: 'applicant', orphanRemoval: true)]
    private Collection $jobApplicationInterviews;

    /**
     * @var Collection<int, JobOffering>
     */
    #[ORM\OneToMany(targetEntity: JobOffering::class, mappedBy: 'jobseeker', orphanRemoval: true)]
    private Collection $jobOfferings;

    /**
     * @var Collection<int, JobOffering>
     */
    #[ORM\OneToMany(targetEntity: JobOffering::class, mappedBy: 'employer', orphanRemoval: true)]
    private Collection $employerJobOferrings;

    /**
     * @var Collection<int, JobHiring>
     */
    #[ORM\OneToMany(targetEntity: JobHiring::class, mappedBy: 'employe', orphanRemoval: true)]
    private Collection $employerHirings;

    /**
     * @var Collection<int, JobHiring>
     */
    #[ORM\OneToMany(targetEntity: JobHiring::class, mappedBy: 'jobseeker', orphanRemoval: true)]
    private Collection $jobseekerHirings;

    /**
     * @var Collection<int, JobSeekerSkill>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerSkill::class, mappedBy: 'jobseeker')]
    private Collection $jobSeekerSkills;

    /**
     * @var Collection<int, JobSeekerSavedJob>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerSavedJob::class, mappedBy: 'jobSeeker', orphanRemoval: true)]
    private Collection $jobSeekerSavedJobs;

    /**
     * @var Collection<int, JobSeekerLanguage>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerLanguage::class, mappedBy: 'jobSeeker', orphanRemoval: true)]
    private Collection $jobSeekerLanguages;

    #[ORM\OneToOne(mappedBy: 'jobSeeker', cascade: ['persist', 'remove'])]
    private ?JobSeekerResume $jobSeekerResume = null;

    /**
     * @var Collection<int, MetierChat>
     */
    #[ORM\OneToMany(targetEntity: MetierChat::class, mappedBy: 'sender', orphanRemoval: true)]
    private Collection $metierChats;

    /**
     * @var Collection<int, MetierChat>
     */
    #[ORM\OneToMany(targetEntity: MetierChat::class, mappedBy: 'receiver', orphanRemoval: true)]
    private Collection $receiverChats;

    /**
     * @var Collection<int, MetierOrder>
     */
    #[ORM\OneToMany(targetEntity: MetierOrder::class, mappedBy: 'customer', orphanRemoval: true)]
    private Collection $orders;

    /**
     * @var Collection<int, MetierOrderPayment>
     */
    #[ORM\OneToMany(targetEntity: MetierOrderPayment::class, mappedBy: 'received_from', orphanRemoval: true)]
    private Collection $payments;

    /**
     * @var Collection<int, JobApplicationInterview>
     */
    #[ORM\OneToMany(targetEntity: JobApplicationInterview::class, mappedBy: 'employer', orphanRemoval: true)]
    private Collection $interviews;

    /**
     * @var Collection<int, EmployerTender>
     */
    #[ORM\OneToMany(targetEntity: EmployerTender::class, mappedBy: 'employer', orphanRemoval: true)]
    private Collection $tenders;

    /**
     * @var Collection<int, EmployerCourses>
     */
    #[ORM\OneToMany(targetEntity: EmployerCourses::class, mappedBy: 'employer')]
    private Collection $employerCourses;

    /**
     * @var Collection<int, JobSeekerEducation>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerEducation::class, mappedBy: 'jobSeeker')]
    private Collection $jobSeekerEducation;

    /**
     * @var Collection<int, JobSeekerCertificate>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerCertificate::class, mappedBy: 'jobSeeker', orphanRemoval: true)]
    private Collection $jobSeekerCertificates;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(nullable: true)]
    private ?int $otp = null;

    /**
     * @var Collection<int, TenderApplication>
     */
    #[ORM\OneToMany(targetEntity: TenderApplication::class, mappedBy: 'applicant', orphanRemoval: true)]
    private Collection $tenderApplications;

    /**
     * @var Collection<int, CourseApplication>
     */
    #[ORM\OneToMany(targetEntity: CourseApplication::class, mappedBy: 'applicant', orphanRemoval: true)]
    private Collection $courseApplications;

    /**
     * @var Collection<int, JobReport>
     */
    #[ORM\OneToMany(targetEntity: JobReport::class, mappedBy: 'reportedBy', orphanRemoval: true)]
    private Collection $jobReports;

    /**
     * @var Collection<int, MetierDownloads>
     */
    #[ORM\OneToMany(targetEntity: MetierDownloads::class, mappedBy: 'client', orphanRemoval: true)]
    private Collection $downloads;

    /**
     * @var Collection<int, MetierNotification>
     */
    #[ORM\OneToMany(targetEntity: MetierNotification::class, cascade: ['persist', 'remove'], mappedBy: 'user', orphanRemoval: true)]
    private Collection $notifications;

    /**
     * @var Collection<int, JobSeekerRecommendedJobs>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerRecommendedJobs::class, mappedBy: 'jobseeker', orphanRemoval: true)]
    private Collection $recommendedJobs;

    /**
     * @var Collection<int, JobSeekerRecommendedJobs>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerRecommendedJobs::class, mappedBy: 'employer', orphanRemoval: true)]
    private Collection $employerRecommendedJobs;

    /**
     * @var Collection<int, JobSeekerJobAlert>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerJobAlert::class, mappedBy: 'jobseeker', orphanRemoval: true)]
    private Collection $jobalerts;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reset_token = null;

    /**
     * @var Collection<int, MetierBlockedUser>
     */
    #[ORM\OneToMany(targetEntity: MetierBlockedUser::class, mappedBy: 'blocked_by')]
    private Collection $metierBlockedByUsers;


    /**
     * @var Collection<int, MetierBlockedUser>
     */
    #[ORM\OneToMany(targetEntity: MetierBlockedUser::class, mappedBy: 'blocked_user')]
    private Collection $metierBlockedUsers;

    /**
     * @var Collection<int, MetierProfileView>
     */
    #[ORM\OneToMany(targetEntity: MetierProfileView::class, mappedBy: 'jobseeker')]
    private Collection $metierProfileViews;

    /**
     * @var Collection<int, MetierProfileView>
     */
    #[ORM\OneToMany(targetEntity: MetierProfileView::class, mappedBy: 'employer', orphanRemoval: true)]
    private Collection $employerProfileViews;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $last_active = null;

    /**
     * @var Collection<int, MetierContacts>
     */
    #[ORM\OneToMany(targetEntity: MetierContacts::class, mappedBy: 'user')]
    private Collection $metierContacts;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $otpExpiration = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $otpEnabled = true;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $otpAttempts = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiration = null;

    /**
     * @var Collection<int, MetierDownloadable>
     */
    #[ORM\OneToMany(targetEntity: MetierDownloadable::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $downloadables;

    /**
     * @var Collection<int, MetierAds>
     */
    #[ORM\OneToMany(targetEntity: MetierAds::class, mappedBy: 'requested_by')]
    private Collection $metierAds;

    #[ORM\Column(nullable: true)]
    private ?bool $is_deleted = null;

    /**
     * @var Collection<int, EmployerStaff>
     */
    #[ORM\OneToMany(targetEntity: EmployerStaff::class, mappedBy: 'employer')]
    private Collection $employerStaff;

    public function __construct()
    {
        $this->employerJobs = new ArrayCollection();
        $this->jobSeekerExperiences = new ArrayCollection();
        $this->jobSeekerWorks = new ArrayCollection();
        $this->jobApplications = new ArrayCollection();
        $this->employerApplications = new ArrayCollection();
        $this->jobApplicationInterviews = new ArrayCollection();
        $this->jobOfferings = new ArrayCollection();
        $this->employerJobOferrings = new ArrayCollection();
        $this->employerHirings = new ArrayCollection();
        $this->jobseekerHirings = new ArrayCollection();
        $this->jobSeekerSkills = new ArrayCollection();
        $this->jobSeekerSavedJobs = new ArrayCollection();
        $this->jobSeekerLanguages = new ArrayCollection();
        $this->metierChats = new ArrayCollection();
        $this->receiverChats = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->interviews = new ArrayCollection();
        $this->tenders = new ArrayCollection();
        $this->employerCourses = new ArrayCollection();
        $this->jobSeekerEducation = new ArrayCollection();
        $this->jobSeekerCertificates = new ArrayCollection();
        $this->tenderApplications = new ArrayCollection();
        $this->courseApplications = new ArrayCollection();
        $this->jobReports = new ArrayCollection();
        $this->downloads = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->recommendedJobs = new ArrayCollection();
        $this->employerRecommendedJobs = new ArrayCollection();
        $this->jobalerts = new ArrayCollection();
        $this->metierBlockedByUsers = new ArrayCollection();
        $this->metierBlockedUsers = new ArrayCollection();
        $this->metierProfileViews = new ArrayCollection();
        $this->employerProfileViews = new ArrayCollection();
        $this->metierContacts = new ArrayCollection();
        $this->downloadables = new ArrayCollection();
        $this->metierAds = new ArrayCollection();
        $this->employerStaff = new ArrayCollection();
    }

    public function getActiveSubscription(): ?MetierOrder
{
    $now = new \DateTimeImmutable();
    
    foreach ($this->orders as $order) {
        if ($order->getValidFrom() <= $now && 
            $order->getValidTo() >= $now &&
            $order->getCategory() === 'jobseeker' &&
            $order->getCustomerType() === 'subscription') {
            return $order;
        }
    }
    
    return null;
}

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(?bool $status): static
    {
        $this->status = $status;

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

    public function getJobseekerDetails(): ?JobseekerDetails
    {
        return $this->jobseekerDetails;
    }

    public function setJobseekerDetails(JobseekerDetails $jobseekerDetails): static
    {
        // set the owning side of the relation if necessary
        if ($jobseekerDetails->getJobseeker() !== $this) {
            $jobseekerDetails->setJobseeker($this);
        }

        $this->jobseekerDetails = $jobseekerDetails;

        return $this;
    }

    public function getEmployerDetails(): ?EmployerDetails
    {
        return $this->employerDetails;
    }

    public function setEmployerDetails(EmployerDetails $employerDetails): static
    {
        // set the owning side of the relation if necessary
        if ($employerDetails->getEmployer() !== $this) {
            $employerDetails->setEmployer($this);
        }

        $this->employerDetails = $employerDetails;

        return $this;
    }

    /**
     * @return Collection<int, EmployerJobs>
     */
    public function getEmployerJobs(): Collection
    {
        return $this->employerJobs;
    }

    // 
    /**
     * @return Collection<int, EmployerJobs>
     */
    public function getPrivateEmployerJobs(): Collection
    {
        return $this->employerJobs->filter(function (EmployerJobs $job) {
            return $job->getIsPrivate() === true;
        });
    }


    public function addEmployerJob(EmployerJobs $employerJob): static
    {
        if (!$this->employerJobs->contains($employerJob)) {
            $this->employerJobs->add($employerJob);
            $employerJob->setEmployer($this);
        }

        return $this;
    }

    public function removeEmployerJob(EmployerJobs $employerJob): static
    {
        if ($this->employerJobs->removeElement($employerJob)) {
            // set the owning side to null (unless already changed)
            if ($employerJob->getEmployer() === $this) {
                $employerJob->setEmployer(null);
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

    public function getCurrentJob(): ?JobSeekerExperience
    {
        foreach($this->jobSeekerExperiences as $experience){
            if($experience->isCurrent()){
                return $experience;
            }
        }
        return null;
    }

    public function addJobSeekerExperience(JobSeekerExperience $jobSeekerExperience): static
    {
        if (!$this->jobSeekerExperiences->contains($jobSeekerExperience)) {
            $this->jobSeekerExperiences->add($jobSeekerExperience);
            $jobSeekerExperience->setJobSeeker($this);
        }

        return $this;
    }

    public function removeJobSeekerExperience(JobSeekerExperience $jobSeekerExperience): static
    {
        if ($this->jobSeekerExperiences->removeElement($jobSeekerExperience)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerExperience->getJobSeeker() === $this) {
                $jobSeekerExperience->setJobSeeker(null);
            }
        }

        return $this;
    }

    public function getProfile(): ?string
    {
        return $this->profile;
    }

    public function setProfile(?string $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerWork>
     */
    public function getJobSeekerWorks(): Collection
    {
        return $this->jobSeekerWorks;
    }
    public function lastWork(): ?JobSeekerWork
    {
        $works = $this->jobSeekerWorks->last();
        


        if($works == null)
            return null;
      return $works;
    }
    public function addJobSeekerWork(JobSeekerWork $jobSeekerWork): static
    {
        if (!$this->jobSeekerWorks->contains($jobSeekerWork)) {
            $this->jobSeekerWorks->add($jobSeekerWork);
            $jobSeekerWork->setJobSeeker($this);
        }

        return $this;
    }

    public function removeJobSeekerWork(JobSeekerWork $jobSeekerWork): static
    {
        if ($this->jobSeekerWorks->removeElement($jobSeekerWork)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerWork->getJobSeeker() === $this) {
                $jobSeekerWork->setJobSeeker(null);
            }
        }

        return $this;
    }

    /**
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
            $jobApplication->setJobSeeker($this);
        }

        return $this;
    }

    public function removeJobApplication(JobApplication $jobApplication): static
    {
        if ($this->jobApplications->removeElement($jobApplication)) {
            // set the owning side to null (unless already changed)
            if ($jobApplication->getJobSeeker() === $this) {
                $jobApplication->setJobSeeker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobApplication>
     */
    public function getEmployerApplications(): Collection
    {
        return $this->employerApplications;
    }

    public function addEmployerApplication(JobApplication $employerApplication): static
    {
        if (!$this->employerApplications->contains($employerApplication)) {
            $this->employerApplications->add($employerApplication);
            $employerApplication->setEmployer($this);
        }

        return $this;
    }

    public function removeEmployerApplication(JobApplication $employerApplication): static
    {
        if ($this->employerApplications->removeElement($employerApplication)) {
            // set the owning side to null (unless already changed)
            if ($employerApplication->getEmployer() === $this) {
                $employerApplication->setEmployer(null);
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
            $jobApplicationInterview->setApplicant($this);
        }

        return $this;
    }

    public function removeJobApplicationInterview(JobApplicationInterview $jobApplicationInterview): static
    {
        if ($this->jobApplicationInterviews->removeElement($jobApplicationInterview)) {
            // set the owning side to null (unless already changed)
            if ($jobApplicationInterview->getApplicant() === $this) {
                $jobApplicationInterview->setApplicant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobOffering>
     */
    public function getJobOfferings(): Collection
    {
        return $this->jobOfferings;
    }

    public function addJobOffering(JobOffering $jobOffering): static
    {
        if (!$this->jobOfferings->contains($jobOffering)) {
            $this->jobOfferings->add($jobOffering);
            $jobOffering->setJobseeker($this);
        }

        return $this;
    }

    public function removeJobOffering(JobOffering $jobOffering): static
    {
        if ($this->jobOfferings->removeElement($jobOffering)) {
            // set the owning side to null (unless already changed)
            if ($jobOffering->getJobseeker() === $this) {
                $jobOffering->setJobseeker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobOffering>
     */
    public function getEmployerJobOferrings(): Collection
    {
        return $this->employerJobOferrings;
    }

    public function addEmployerJobOferring(JobOffering $employerJobOferring): static
    {
        if (!$this->employerJobOferrings->contains($employerJobOferring)) {
            $this->employerJobOferrings->add($employerJobOferring);
            $employerJobOferring->setEmployer($this);
        }

        return $this;
    }

    public function removeEmployerJobOferring(JobOffering $employerJobOferring): static
    {
        if ($this->employerJobOferrings->removeElement($employerJobOferring)) {
            // set the owning side to null (unless already changed)
            if ($employerJobOferring->getEmployer() === $this) {
                $employerJobOferring->setEmployer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobHiring>
     */
    public function getEmployerHirings(): Collection
    {
        return $this->employerHirings;
    }

    public function addEmployerHiring(JobHiring $employerHiring): static
    {
        if (!$this->employerHirings->contains($employerHiring)) {
            $this->employerHirings->add($employerHiring);
            $employerHiring->setEmploye($this);
        }

        return $this;
    }

    public function removeEmployerHiring(JobHiring $employerHiring): static
    {
        if ($this->employerHirings->removeElement($employerHiring)) {
            // set the owning side to null (unless already changed)
            if ($employerHiring->getEmploye() === $this) {
                $employerHiring->setEmploye(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobHiring>
     */
    public function getJobseekerHirings(): Collection
    {
        return $this->jobseekerHirings;
    }

    public function addJobseekerHiring(JobHiring $jobseekerHiring): static
    {
        if (!$this->jobseekerHirings->contains($jobseekerHiring)) {
            $this->jobseekerHirings->add($jobseekerHiring);
            $jobseekerHiring->setJobseeker($this);
        }

        return $this;
    }

    public function removeJobseekerHiring(JobHiring $jobseekerHiring): static
    {
        if ($this->jobseekerHirings->removeElement($jobseekerHiring)) {
            // set the owning side to null (unless already changed)
            if ($jobseekerHiring->getJobseeker() === $this) {
                $jobseekerHiring->setJobseeker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerSkill>
     */
    public function getJobSeekerSkills(): Collection
    {
        return $this->jobSeekerSkills;
    }

    public function addJobSeekerSkill(JobSeekerSkill $jobSeekerSkill): static
    {
        if (!$this->jobSeekerSkills->contains($jobSeekerSkill)) {
            $this->jobSeekerSkills->add($jobSeekerSkill);
            $jobSeekerSkill->setJobseeker($this);
        }

        return $this;
    }

    public function removeJobSeekerSkill(JobSeekerSkill $jobSeekerSkill): static
    {
        if ($this->jobSeekerSkills->removeElement($jobSeekerSkill)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerSkill->getJobseeker() === $this) {
                $jobSeekerSkill->setJobseeker(null);
            }
        }

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
            $jobSeekerSavedJob->setJobSeeker($this);
        }

        return $this;
    }

    public function removeJobSeekerSavedJob(JobSeekerSavedJob $jobSeekerSavedJob): static
    {
        if ($this->jobSeekerSavedJobs->removeElement($jobSeekerSavedJob)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerSavedJob->getJobSeeker() === $this) {
                $jobSeekerSavedJob->setJobSeeker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerLanguage>
     */
    public function getJobSeekerLanguages(): Collection
    {
        return $this->jobSeekerLanguages;
    }

    public function addJobSeekerLanguage(JobSeekerLanguage $jobSeekerLanguage): static
    {
        if (!$this->jobSeekerLanguages->contains($jobSeekerLanguage)) {
            $this->jobSeekerLanguages->add($jobSeekerLanguage);
            $jobSeekerLanguage->setJobSeeker($this);
        }

        return $this;
    }

    public function removeJobSeekerLanguage(JobSeekerLanguage $jobSeekerLanguage): static
    {
        if ($this->jobSeekerLanguages->removeElement($jobSeekerLanguage)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerLanguage->getJobSeeker() === $this) {
                $jobSeekerLanguage->setJobSeeker(null);
            }
        }

        return $this;
    }

    public function getJobSeekerResume(): ?JobSeekerResume
    {
        return $this->jobSeekerResume;
    }

    public function setJobSeekerResume(JobSeekerResume $jobSeekerResume): static
    {
        // set the owning side of the relation if necessary
        if ($jobSeekerResume->getJobSeeker() !== $this) {
            $jobSeekerResume->setJobSeeker($this);
        }

        $this->jobSeekerResume = $jobSeekerResume;

        return $this;
    }

    /**
     * @return Collection<int, MetierChat>
     */
    public function getMetierChats(): Collection
    {
        return $this->metierChats;
    }

    public function addMetierChat(MetierChat $metierChat): static
    {
        if (!$this->metierChats->contains($metierChat)) {
            $this->metierChats->add($metierChat);
            $metierChat->setSender($this);
        }

        return $this;
    }

    public function removeMetierChat(MetierChat $metierChat): static
    {
        if ($this->metierChats->removeElement($metierChat)) {
            // set the owning side to null (unless already changed)
            if ($metierChat->getSender() === $this) {
                $metierChat->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierChat>
     */
    public function getReceiverChats(): Collection
    {
        return $this->receiverChats;
    }

    public function addReceiverChat(MetierChat $receiverChat): static
    {
        if (!$this->receiverChats->contains($receiverChat)) {
            $this->receiverChats->add($receiverChat);
            $receiverChat->setReceiver($this);
        }

        return $this;
    }

    public function removeReceiverChat(MetierChat $receiverChat): static
    {
        if ($this->receiverChats->removeElement($receiverChat)) {
            // set the owning side to null (unless already changed)
            if ($receiverChat->getReceiver() === $this) {
                $receiverChat->setReceiver(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierOrder>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function getLastLogin(): string
{
    if (!$this->getLastActive()) {
        return 'Inactive';
    }

    return Carbon::instance($this->last_active)->diffForHumans();
}

    public function addOrder(MetierOrder $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setCustomer($this);
        }

        return $this;
    }

    public function removeOrder(MetierOrder $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getCustomer() === $this) {
                $order->setCustomer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierOrderPayment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(MetierOrderPayment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setReceivedFrom($this);
        }

        return $this;
    }

    public function removePayment(MetierOrderPayment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getReceivedFrom() === $this) {
                $payment->setReceivedFrom(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobApplicationInterview>
     */
    public function getInterviews(): Collection
    {
        return $this->interviews;
    }

    public function addInterview(JobApplicationInterview $interview): static
    {
        if (!$this->interviews->contains($interview)) {
            $this->interviews->add($interview);
            $interview->setEmployer($this);
        }

        return $this;
    }

    public function removeInterview(JobApplicationInterview $interview): static
    {
        if ($this->interviews->removeElement($interview)) {
            // set the owning side to null (unless already changed)
            if ($interview->getEmployer() === $this) {
                $interview->setEmployer(null);
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
            $tender->setEmployer($this);
        }

        return $this;
    }

    public function removeTender(EmployerTender $tender): static
    {
        if ($this->tenders->removeElement($tender)) {
            // set the owning side to null (unless already changed)
            if ($tender->getEmployer() === $this) {
                $tender->setEmployer(null);
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
            $employerCourse->setEmployer($this);
        }

        return $this;
    }

    public function removeEmployerCourse(EmployerCourses $employerCourse): static
    {
        if ($this->employerCourses->removeElement($employerCourse)) {
            // set the owning side to null (unless already changed)
            if ($employerCourse->getEmployer() === $this) {
                $employerCourse->setEmployer(null);
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
            $jobSeekerEducation->setJobSeeker($this);
        }

        return $this;
    }

    public function removeJobSeekerEducation(JobSeekerEducation $jobSeekerEducation): static
    {
        if ($this->jobSeekerEducation->removeElement($jobSeekerEducation)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerEducation->getJobSeeker() === $this) {
                $jobSeekerEducation->setJobSeeker(null);
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
            $jobSeekerCertificate->setJobSeeker($this);
        }

        return $this;
    }

    public function removeJobSeekerCertificate(JobSeekerCertificate $jobSeekerCertificate): static
    {
        if ($this->jobSeekerCertificates->removeElement($jobSeekerCertificate)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerCertificate->getJobSeeker() === $this) {
                $jobSeekerCertificate->setJobSeeker(null);
            }
        }

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getOtp(): ?int
    {
        return $this->otp;
    }

    public function setOtp(?int $otp): static
    {
        $this->otp = $otp;

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
            $tenderApplication->setApplicant($this);
        }

        return $this;
    }

    public function removeTenderApplication(TenderApplication $tenderApplication): static
    {
        if ($this->tenderApplications->removeElement($tenderApplication)) {
            // set the owning side to null (unless already changed)
            if ($tenderApplication->getApplicant() === $this) {
                $tenderApplication->setApplicant(null);
            }
        }

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
            $courseApplication->setApplicant($this);
        }

        return $this;
    }

    public function removeCourseApplication(CourseApplication $courseApplication): static
    {
        if ($this->courseApplications->removeElement($courseApplication)) {
            // set the owning side to null (unless already changed)
            if ($courseApplication->getApplicant() === $this) {
                $courseApplication->setApplicant(null);
            }
        }

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
            $jobReport->setReportedBy($this);
        }

        return $this;
    }

    public function removeJobReport(JobReport $jobReport): static
    {
        if ($this->jobReports->removeElement($jobReport)) {
            // set the owning side to null (unless already changed)
            if ($jobReport->getReportedBy() === $this) {
                $jobReport->setReportedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierDownloads>
     */
    public function getDownloads(): Collection
    {
        return $this->downloads;
    }

    public function addDownload(MetierDownloads $download): static
    {
        if (!$this->downloads->contains($download)) {
            $this->downloads->add($download);
            $download->setClient($this);
        }

        return $this;
    }

    public function removeDownload(MetierDownloads $download): static
    {
        if ($this->downloads->removeElement($download)) {
            // set the owning side to null (unless already changed)
            if ($download->getClient() === $this) {
                $download->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierNotification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(MetierNotification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(MetierNotification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerRecommendedJobs>
     */
    public function getRecommendedJobs(): Collection
    {
        return $this->recommendedJobs;
    }

    public function addRecommendedJob(JobSeekerRecommendedJobs $recommendedJob): static
    {
        if (!$this->recommendedJobs->contains($recommendedJob)) {
            $this->recommendedJobs->add($recommendedJob);
            $recommendedJob->setJobseeker($this);
        }

        return $this;
    }

    public function removeRecommendedJob(JobSeekerRecommendedJobs $recommendedJob): static
    {
        if ($this->recommendedJobs->removeElement($recommendedJob)) {
            // set the owning side to null (unless already changed)
            if ($recommendedJob->getJobseeker() === $this) {
                $recommendedJob->setJobseeker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerRecommendedJobs>
     */
    public function getEmployerRecommendedJobs(): Collection
    {
        return $this->employerRecommendedJobs;
    }

    public function addEmployerRecommendedJob(JobSeekerRecommendedJobs $employerRecommendedJob): static
    {
        if (!$this->employerRecommendedJobs->contains($employerRecommendedJob)) {
            $this->employerRecommendedJobs->add($employerRecommendedJob);
            $employerRecommendedJob->setEmployer($this);
        }

        return $this;
    }

    public function removeEmployerRecommendedJob(JobSeekerRecommendedJobs $employerRecommendedJob): static
    {
        if ($this->employerRecommendedJobs->removeElement($employerRecommendedJob)) {
            // set the owning side to null (unless already changed)
            if ($employerRecommendedJob->getEmployer() === $this) {
                $employerRecommendedJob->setEmployer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerJobAlert>
     */
    public function getJobalerts(): Collection
    {
        return $this->jobalerts;
    }

    public function addJobalert(JobSeekerJobAlert $jobalert): static
    {
        if (!$this->jobalerts->contains($jobalert)) {
            $this->jobalerts->add($jobalert);
            $jobalert->setJobseeker($this);
        }

        return $this;
    }

    public function removeJobalert(JobSeekerJobAlert $jobalert): static
    {
        if ($this->jobalerts->removeElement($jobalert)) {
            // set the owning side to null (unless already changed)
            if ($jobalert->getJobseeker() === $this) {
                $jobalert->setJobseeker(null);
            }
        }

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->reset_token;
    }

    public function setResetToken(?string $reset_token): static
    {
        $this->reset_token = $reset_token;

        return $this;
    }

    /**
     * @return Collection<int, MetierBlockedUser>
     */
    public function getMetierBlockedByUsers(): Collection
    {
        return $this->metierBlockedByUsers;
    }

    public function addMetierBlockedByUser(MetierBlockedUser $metierBlockedByUser): static
    {
        if (!$this->metierBlockedByUsers->contains($metierBlockedByUser)) {
            $this->metierBlockedByUsers->add($metierBlockedByUser);
            $metierBlockedByUser->setBlockedBy($this);
        }

        return $this;
    }

    public function removeMetierBlockedByUser(MetierBlockedUser $metierBlockedByUser): static
    {
        if ($this->metierBlockedByUsers->removeElement($metierBlockedByUser)) {
            // set the owning side to null (unless already changed)
            if ($metierBlockedByUser->getBlockedBy() === $this) {
                $metierBlockedByUser->setBlockedBy(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection<int, MetierBlockedUser>
     */
    public function getMetierBlockedUsers(): Collection
    {
        return $this->metierBlockedUsers;
    }

    public function addMetierBlockedUser(MetierBlockedUser $metierBlockedUser): static
    {
        if (!$this->metierBlockedUsers->contains($metierBlockedUser)) {
            $this->metierBlockedUsers->add($metierBlockedUser);
            $metierBlockedUser->setBlockedUser(blocked_user: $this);
        }

        return $this;
    }

    public function removeMetierBlockedUser(MetierBlockedUser $metierBlockedUser): static
    {
        if ($this->metierBlockedByUsers->removeElement($metierBlockedUser)) {
            // set the owning side to null (unless already changed)
            if ($metierBlockedUser->getBlockedUser() === $this) {
                $metierBlockedUser->setBlockedUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierProfileView>
     */
    public function getMetierProfileViews(): Collection
    {
        return $this->metierProfileViews;
    }

    public function addMetierProfileView(MetierProfileView $metierProfileView): static
    {
        if (!$this->metierProfileViews->contains($metierProfileView)) {
            $this->metierProfileViews->add($metierProfileView);
            $metierProfileView->setJobseeker($this);
        }

        return $this;
    }

    public function removeMetierProfileView(MetierProfileView $metierProfileView): static
    {
        if ($this->metierProfileViews->removeElement($metierProfileView)) {
            // set the owning side to null (unless already changed)
            if ($metierProfileView->getJobseeker() === $this) {
                $metierProfileView->setJobseeker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierProfileView>
     */
    public function getEmployerProfileViews(): Collection
    {
        return $this->employerProfileViews;
    }

    public function addEmployerProfileView(MetierProfileView $employerProfileView): static
    {
        if (!$this->employerProfileViews->contains($employerProfileView)) {
            $this->employerProfileViews->add($employerProfileView);
            $employerProfileView->setEmployer($this);
        }

        return $this;
    }

    public function removeEmployerProfileView(MetierProfileView $employerProfileView): static
    {
        if ($this->employerProfileViews->removeElement($employerProfileView)) {
            // set the owning side to null (unless already changed)
            if ($employerProfileView->getEmployer() === $this) {
                $employerProfileView->setEmployer(null);
            }
        }

        return $this;
    }

    public function getLastActive(): ?\DateTimeInterface
    {
        return $this->last_active;
    }

    public function setLastActive(?\DateTimeInterface $last_active): static
    {
        $this->last_active = $last_active;

        return $this;
    }

    /**
     * @return Collection<int, MetierContacts>
     */
    public function getMetierContacts(): Collection
    {
        return $this->metierContacts;
    }

    public function addMetierContact(MetierContacts $metierContact): static
    {
        if (!$this->metierContacts->contains($metierContact)) {
            $this->metierContacts->add($metierContact);
            $metierContact->setUser($this);
        }

        return $this;
    }

    public function removeMetierContact(MetierContacts $metierContact): static
    {
        if ($this->metierContacts->removeElement($metierContact)) {
            // set the owning side to null (unless already changed)
            if ($metierContact->getUser() === $this) {
                $metierContact->setUser(null);
            }
        }

        return $this;
    }

    public function getUnRedReceivedChatsCount()
    {
        $blocked = $this->getMetierBlockedUsers()->map(function($user) {
            return $user->getBlockedBy()->getId();
        });
        
        $count = $this->getReceiverChats()->filter(function($chat) use ($blocked) {
            return !$chat->isSeen() && !in_array($chat->getReceiver()->getId(), $blocked->toArray()) && !in_array($chat->getSender()->getId(), $blocked->toArray());
        })->count();
        
        return $count;
    }

    public function getOtpExpiration(): ?\DateTimeInterface
    {
        return $this->otpExpiration;
    }

    public function setOtpExpiration(?\DateTimeInterface $otpExpiration): static
    {
        $this->otpExpiration = $otpExpiration;

        return $this;
    }

    public function isOtpEnabled(): ?bool
    {
        return $this->otpEnabled;
    }

    public function setOtpEnabled(bool $otpEnabled): static
    {
        $this->otpEnabled = $otpEnabled;

        return $this;
    }

    public function getOtpAttempts(): ?int
    {
        return $this->otpAttempts;
    }

    public function setOtpAttempts(int $otpAttempts): static
    {
        $this->otpAttempts = $otpAttempts;

        return $this;
    }

    public function getResetTokenExpiration(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiration;
    }

    public function setResetTokenExpiration(?\DateTimeInterface $resetTokenExpiration): static
    {
        $this->resetTokenExpiration = $resetTokenExpiration;

        return $this;
    }

    /**
     * @return Collection<int, MetierDownloadable>
     */
    public function getDownloadables(): Collection
    {
        return $this->downloadables;
    }

    public function addDownloadable(MetierDownloadable $downloadable): static
    {
        if (!$this->downloadables->contains($downloadable)) {
            $this->downloadables->add($downloadable);
            $downloadable->setUser($this);
        }

        return $this;
    }

    public function removeDownloadable(MetierDownloadable $downloadable): static
    {
        if ($this->downloadables->removeElement($downloadable)) {
            // set the owning side to null (unless already changed)
            if ($downloadable->getUser() === $this) {
                $downloadable->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierAds>
     */
    public function getMetierAds(): Collection
    {
        return $this->metierAds;
    }

    public function addMetierAd(MetierAds $metierAd): static
    {
        if (!$this->metierAds->contains($metierAd)) {
            $this->metierAds->add($metierAd);
            $metierAd->setRequestedBy($this);
        }

        return $this;
    }

    public function removeMetierAd(MetierAds $metierAd): static
    {
        if ($this->metierAds->removeElement($metierAd)) {
            // set the owning side to null (unless already changed)
            if ($metierAd->getRequestedBy() === $this) {
                $metierAd->setRequestedBy(null);
            }
        }

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
     * @return Collection<int, EmployerStaff>
     */
    public function getEmployerStaff(): Collection
    {
        return $this->employerStaff;
    }

    public function addEmployerStaff(EmployerStaff $employerStaff): static
    {
        if (!$this->employerStaff->contains($employerStaff)) {
            $this->employerStaff->add($employerStaff);
            $employerStaff->setEmployer($this);
        }

        return $this;
    }

    public function removeEmployerStaff(EmployerStaff $employerStaff): static
    {
        if ($this->employerStaff->removeElement($employerStaff)) {
            // set the owning side to null (unless already changed)
            if ($employerStaff->getEmployer() === $this) {
                $employerStaff->setEmployer(null);
            }
        }

        return $this;
    }
}