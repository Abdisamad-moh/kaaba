<?php 
// src/Entity/SmsJob.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'sms_jobs')]
class SmsJob
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $createdBy;

    #[ORM\Column(type: 'json')]
    private array $filters = [];

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'pending'; // pending, processing, completed, failed

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $totalRecipients = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $sentCount = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $failedCount = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $error = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTime();
    }

    // Getters
    public function getId(): Uuid { return $this->id; }
    public function getCreatedBy(): User { return $this->createdBy; }
    public function getFilters(): array { return $this->filters; }
    public function getMessage(): string { return $this->message; }
    public function getStatus(): string { return $this->status; }
    public function getTotalRecipients(): ?int { return $this->totalRecipients; }
    public function getSentCount(): ?int { return $this->sentCount; }
    public function getFailedCount(): ?int { return $this->failedCount; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getCompletedAt(): ?\DateTimeInterface { return $this->completedAt; }
    public function getError(): ?string { return $this->error; }

    // Setters
    public function setCreatedBy(User $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function setFilters(array $filters): self { $this->filters = $filters; return $this; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function setTotalRecipients(?int $totalRecipients): self { $this->totalRecipients = $totalRecipients; return $this; }
    public function setSentCount(?int $sentCount): self { $this->sentCount = $sentCount; return $this; }
    public function setFailedCount(?int $failedCount): self { $this->failedCount = $failedCount; return $this; }
    public function setCompletedAt(?\DateTimeInterface $completedAt): self { $this->completedAt = $completedAt; return $this; }
    public function setError(?string $error): self { $this->error = $error; return $this; }
}