<?php

namespace App\Entity;

use App\Repository\JobApplicationQuestionAnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobApplicationQuestionAnswerRepository::class)]
class JobApplicationQuestionAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'jobApplicationQuestionAnswers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?JobApplication $application = null;

    #[ORM\ManyToOne(inversedBy: 'jobApplicationQuestionAnswers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EmployerJobQuestion $question = null;

    #[ORM\ManyToOne(inversedBy: 'jobApplicationQuestionAnswers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EmployerJobQuestionAnswer $answer = null;

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

    public function getQuestion(): ?EmployerJobQuestion
    {
        return $this->question;
    }

    public function setQuestion(?EmployerJobQuestion $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswer(): ?EmployerJobQuestionAnswer
    {
        return $this->answer;
    }

    public function setAnswer(?EmployerJobQuestionAnswer $answer): static
    {
        $this->answer = $answer;

        return $this;
    }
}
