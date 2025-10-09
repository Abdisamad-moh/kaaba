<?php

namespace App\Entity;

use App\Repository\JobApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: JobApplicationRepository::class)]
class JobApplication
{
    use Timestamps;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'jobApplications')]
    #[ORM\JoinColumn(nullable: true)]
    private ?EmployerJobs $job = null;

    #[ORM\ManyToOne(inversedBy: 'jobApplications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobSeeker = null;

    #[ORM\ManyToOne(inversedBy: 'employerApplications')]
    private ?User $employer = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $document = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

  
    /**
     * @var Collection<int, JobApplicationInterview>
     */
    #[ORM\OneToMany(targetEntity: JobApplicationInterview::class, mappedBy: 'application')]
    private Collection $interviews;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cv = null;

    /**
     * @var Collection<int, JobApplicationQuestionAnswer>
     */
    #[ORM\OneToMany(targetEntity: JobApplicationQuestionAnswer::class, mappedBy: 'application', orphanRemoval: true)]
    private Collection $jobApplicationQuestionAnswers;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejection_note = null;

    /**
     * @var Collection<int, JobApplicationShortlist>
     */
    #[ORM\OneToMany(targetEntity: JobApplicationShortlist::class, mappedBy: 'application', orphanRemoval: true)]
    private Collection $automaticShortlists;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverLetter = null;



    public function __construct()
    {
        $this->interviews = new ArrayCollection();
        $this->jobApplicationQuestionAnswers = new ArrayCollection();
        $this->automaticShortlists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function lastInterview(): ?JobApplicationInterview
    {
    
        $lastInterview = $this->interviews->last();
     
      
        return $lastInterview;
    }

    public function getJob(): ?EmployerJobs
    {
        return $this->job;
    }

    public function setJob(?EmployerJobs $job): static
    {
        $this->job = $job;

        return $this;
    }

    public function getJobSeeker(): ?User
    {
        return $this->jobSeeker;
    }

    public function setJobSeeker(?User $jobSeeker): static
    {
        $this->jobSeeker = $jobSeeker;

        return $this;
    }

    public function getDocument(): ?string
    {
        return $this->document;
    }

    public function setDocument(?string $document): static
    {
        $this->document = $document;

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

    public function getEmployer(): ?User
    {
        return $this->employer;
    }

    public function setEmployer(?User $employer): static
    {
        $this->employer = $employer;

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
            $interview->setApplication($this);
        }

        return $this;
    }

    public function removeInterview(JobApplicationInterview $interview): static
    {
        if ($this->interviews->removeElement($interview)) {
            // set the owning side to null (unless already changed)
            if ($interview->getApplication() === $this) {
                $interview->setApplication(null);
            }
        }

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
     * @return Collection<int, JobApplicationQuestionAnswer>
     */
    public function getJobApplicationQuestionAnswers(): Collection
    {
        return $this->jobApplicationQuestionAnswers;
    }

    public function addJobApplicationQuestionAnswer(JobApplicationQuestionAnswer $jobApplicationQuestionAnswer): static
    {
        if (!$this->jobApplicationQuestionAnswers->contains($jobApplicationQuestionAnswer)) {
            $this->jobApplicationQuestionAnswers->add($jobApplicationQuestionAnswer);
            $jobApplicationQuestionAnswer->setApplication($this);
        }

        return $this;
    }

    public function removeJobApplicationQuestionAnswer(JobApplicationQuestionAnswer $jobApplicationQuestionAnswer): static
    {
        if ($this->jobApplicationQuestionAnswers->removeElement($jobApplicationQuestionAnswer)) {
            // set the owning side to null (unless already changed)
            if ($jobApplicationQuestionAnswer->getApplication() === $this) {
                $jobApplicationQuestionAnswer->setApplication(null);
            }
        }

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

    public function getRejectionNote(): ?string
    {
        return $this->rejection_note;
    }

    public function setRejectionNote(?string $rejection_note): static
    {
        $this->rejection_note = $rejection_note;

        return $this;
    }

    /**
     * @return Collection<int, JobApplicationShortlist>
     */
    public function getAutomaticShortlists(): Collection
    {
        return $this->automaticShortlists;
    }

    public function addAutomaticShortlist(JobApplicationShortlist $automaticShortlist): static
    {
        if (!$this->automaticShortlists->contains($automaticShortlist)) {
            $this->automaticShortlists->add($automaticShortlist);
            $automaticShortlist->setApplication($this);
        }

        return $this;
    }

    public function removeAutomaticShortlist(JobApplicationShortlist $automaticShortlist): static
    {
        if ($this->automaticShortlists->removeElement($automaticShortlist)) {
            // set the owning side to null (unless already changed)
            if ($automaticShortlist->getApplication() === $this) {
                $automaticShortlist->setApplication(null);
            }
        }

        return $this;
    }

    public function getCoverLetter(): ?string
    {
        return $this->coverLetter;
    }

    public function setCoverLetter(?string $coverLetter): static
    {
        $this->coverLetter = $coverLetter;

        return $this;
    }

}
