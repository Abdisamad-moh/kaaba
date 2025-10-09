<?php

namespace App\Entity;

use App\Repository\EmployerJobQuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployerJobQuestionRepository::class)]
class EmployerJobQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'employerJobQuestions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EmployerJobs $job = null;

    /**
     * @var Collection<int, EmployerJobQuestionAnswer>
     */
    #[ORM\OneToMany(targetEntity: EmployerJobQuestionAnswer::class, mappedBy: 'question', cascade: ['persist'])]
    private Collection $employerJobQuestionAnswers;

    /**
     * @var Collection<int, JobApplicationQuestionAnswer>
     */
    #[ORM\OneToMany(targetEntity: JobApplicationQuestionAnswer::class, mappedBy: 'question', orphanRemoval: true)]
    private Collection $jobApplicationQuestionAnswers;

    public function __construct()
    {
        $this->employerJobQuestionAnswers = new ArrayCollection();
        $this->jobApplicationQuestionAnswers = new ArrayCollection();
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

    public function getJob(): ?EmployerJobs
    {
        return $this->job;
    }

    public function setJob(?EmployerJobs $job): static
    {
        $this->job = $job;

        return $this;
    }

    /**
     * @return Collection<int, EmployerJobQuestionAnswer>
     */
    public function getEmployerJobQuestionAnswers(): Collection
    {
        return $this->employerJobQuestionAnswers;
    }

    public function addEmployerJobQuestionAnswer(EmployerJobQuestionAnswer $employerJobQuestionAnswer): static
    {
        if (!$this->employerJobQuestionAnswers->contains($employerJobQuestionAnswer)) {
            $this->employerJobQuestionAnswers->add($employerJobQuestionAnswer);
            $employerJobQuestionAnswer->setQuestion($this);
        }

        return $this;
    }

    public function removeEmployerJobQuestionAnswer(EmployerJobQuestionAnswer $employerJobQuestionAnswer): static
    {
        if ($this->employerJobQuestionAnswers->removeElement($employerJobQuestionAnswer)) {
            // set the owning side to null (unless already changed)
            if ($employerJobQuestionAnswer->getQuestion() === $this) {
                $employerJobQuestionAnswer->setQuestion(null);
            }
        }

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
            $jobApplicationQuestionAnswer->setQuestion($this);
        }

        return $this;
    }

    public function removeJobApplicationQuestionAnswer(JobApplicationQuestionAnswer $jobApplicationQuestionAnswer): static
    {
        if ($this->jobApplicationQuestionAnswers->removeElement($jobApplicationQuestionAnswer)) {
            // set the owning side to null (unless already changed)
            if ($jobApplicationQuestionAnswer->getQuestion() === $this) {
                $jobApplicationQuestionAnswer->setQuestion(null);
            }
        }

        return $this;
    }
}
