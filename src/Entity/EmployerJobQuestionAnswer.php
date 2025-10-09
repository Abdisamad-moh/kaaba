<?php

namespace App\Entity;

use App\Repository\EmployerJobQuestionAnswerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployerJobQuestionAnswerRepository::class)]
class EmployerJobQuestionAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable:true)]
    private ?bool $is_right = null;

    #[ORM\ManyToOne(inversedBy: 'employerJobQuestionAnswers')]
    #[ORM\JoinColumn(nullable: true)]
    private ?EmployerJobQuestion $question = null;

    /**
     * @var Collection<int, JobApplicationQuestionAnswer>
     */
    #[ORM\OneToMany(targetEntity: JobApplicationQuestionAnswer::class, mappedBy: 'answer', orphanRemoval: true)]
    private Collection $jobApplicationQuestionAnswers;

    public function __construct()
    {
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isRight(): ?bool
    {
        return $this->is_right;
    }

    public function setIsRight(bool $is_right): static
    {
        $this->is_right = $is_right;

        return $this;
    }

    public function getQuestion(): ?EmployerJobQuestion
    {
        return $this->question;
    }

    public function setQuestion(?EmployerJobQuestion $question): static
    {
        $this->question = $question;

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
            $jobApplicationQuestionAnswer->setAnswer($this);
        }

        return $this;
    }

    public function removeJobApplicationQuestionAnswer(JobApplicationQuestionAnswer $jobApplicationQuestionAnswer): static
    {
        if ($this->jobApplicationQuestionAnswers->removeElement($jobApplicationQuestionAnswer)) {
            // set the owning side to null (unless already changed)
            if ($jobApplicationQuestionAnswer->getAnswer() === $this) {
                $jobApplicationQuestionAnswer->setAnswer(null);
            }
        }

        return $this;
    }
}
