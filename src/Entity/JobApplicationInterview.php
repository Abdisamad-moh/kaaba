<?php

namespace App\Entity;

use App\Repository\JobApplicationInterviewRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: JobApplicationInterviewRepository::class)]
class JobApplicationInterview
{
    use Timestamps;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'interviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?JobApplication $application = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $notes = null;

    #[ORM\ManyToOne(inversedBy: 'jobApplicationInterviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $applicant = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $result_comment = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $meeting_link = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column]
    private ?int $rounds = null;

    #[ORM\ManyToOne(inversedBy: 'jobApplicationInterviews')]
    private ?MetierCountry $country = null;

    #[ORM\ManyToOne(inversedBy: 'jobApplicationInterviews')]
    private ?MetierCity $city = null;

    /**
     * @var Collection<int, JobApplicationInterviewRound>
     */
    #[ORM\OneToMany(targetEntity: JobApplicationInterviewRound::class, mappedBy: 'interview', orphanRemoval: true)]
    private Collection $jobApplicationInterviewRounds;

    #[ORM\ManyToOne(inversedBy: 'interviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $employer = null;

    public function __construct()
    {
        $this->jobApplicationInterviewRounds = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplication(): ?JobApplication
    {
        return $this->application;
    }

    public function setApplication(?JobApplication $application): static
    {
        $this->application = $application;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getApplicant(): ?User
    {
        return $this->applicant;
    }

    public function setApplicant(?User $applicant): static
    {
        $this->applicant = $applicant;

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

    public function getResultComment(): ?string
    {
        return $this->result_comment;
    }

    public function setResultComment(?string $result_comment): static
    {
        $this->result_comment = $result_comment;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getMeetingLink(): ?string
    {
        return $this->meeting_link;
    }

    public function setMeetingLink(?string $meeting_link): static
    {
        $this->meeting_link = $meeting_link;

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

    public function getRounds(): ?int
    {
        return $this->rounds;
    }

    public function setRounds(int $rounds): static
    {
        $this->rounds = $rounds;

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
    public function lastRound(): ?JobApplicationInterviewRound
    {
        $bills = $this->jobApplicationInterviewRounds->last();
     
      
        return $bills;
    }
    public function noneCompleteRound(): ?JobApplicationInterviewRound
    {
        $bills = $this->jobApplicationInterviewRounds->last();
        
        if($bills->getStatus() === "interview scheduled" XOR $bills->getStatus() === "rejected" XOR  $bills->getStatus() === "selected" XOR $bills->getStatus() === "next round")
            return $bills;

        return false;
        
    }

    /**
     * @return Collection<int, JobApplicationInterviewRound>
     */
    public function getJobApplicationInterviewRounds(): Collection
    {
        return $this->jobApplicationInterviewRounds;
    }

    public function addJobApplicationInterviewRound(JobApplicationInterviewRound $jobApplicationInterviewRound): static
    {
        if (!$this->jobApplicationInterviewRounds->contains($jobApplicationInterviewRound)) {
            $this->jobApplicationInterviewRounds->add($jobApplicationInterviewRound);
            $jobApplicationInterviewRound->setInterview($this);
        }

        return $this;
    }

    public function removeJobApplicationInterviewRound(JobApplicationInterviewRound $jobApplicationInterviewRound): static
    {
        if ($this->jobApplicationInterviewRounds->removeElement($jobApplicationInterviewRound)) {
            // set the owning side to null (unless already changed)
            if ($jobApplicationInterviewRound->getInterview() === $this) {
                $jobApplicationInterviewRound->setInterview(null);
            }
        }

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
}
