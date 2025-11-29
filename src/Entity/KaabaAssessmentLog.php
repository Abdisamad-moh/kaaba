<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class KaabaAssessmentLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'assessmentLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?KaabaAssessment $assessment = null;

    #[ORM\Column(length: 50)]
    private ?string $action = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getAssessment(): ?KaabaAssessment
    {
        return $this->assessment;
    }

    public function setAssessment(?KaabaAssessment $assessment): static
    {
        $this->assessment = $assessment;
        return $this;
    }

    public function getAction(): ?string { return $this->action; }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getNote(): ?string { return $this->note; }

    public function setNote(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getUser(): ?User { return $this->user; }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
