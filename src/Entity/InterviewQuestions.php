<?php

namespace App\Entity;

use App\Repository\InterviewQuestionsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterviewQuestionsRepository::class)]
class InterviewQuestions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

   
    #[ORM\Column(type: "text")]
    private ?string $q = null;

    // Convert private ?string $a to a long text field
    #[ORM\Column(type: "text")]
    private ?string $a = null;

    // Convert private ?string $r to a long text field
    #[ORM\Column(type: "text", nullable: true)]
    private ?string $r = null;

    #[ORM\ManyToOne(inversedBy: 'interviewQuestions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?InterviewQuestionJobTitle $job_type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQ(): ?string
    {
        return $this->q;
    }

    public function setQ(string $q): static
    {
        $this->q = $q;

        return $this;
    }

    public function getA(): ?string
    {
        return $this->a;
    }

    public function setA(string $a): static
    {
        $this->a = $a;

        return $this;
    }

    public function getR(): ?string
    {
        return $this->r;
    }

    public function setR(?string $r): static
    {
        $this->r = $r;

        return $this;
    }

    public function getJobType(): ?InterviewQuestionJobTitle
    {
        return $this->job_type;
    }

    public function setJobType(?InterviewQuestionJobTitle $job_type): static
    {
        $this->job_type = $job_type;

        return $this;
    }
}
