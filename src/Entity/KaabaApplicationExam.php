<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\KaabaApplicationExamRepository;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: KaabaApplicationExamRepository::class)]
#[ORM\Table(name: "kaaba_application_exams")]
class KaabaApplicationExam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // One exam belongs to one application
    #[ORM\OneToOne(inversedBy: 'exam')]
    #[ORM\JoinColumn(nullable: false)]
    private ?KaabaApplication $application = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachment = null;

#[ORM\Column(length: 20, nullable: true)]
private ?string $examResult = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: "guid")]
    private string $uuid;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->uuid = Uuid::v4();
    }

    // --------- Getters & Setters ---------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplication(): ?KaabaApplication
    {
        return $this->application;
    }

    public function setApplication(?KaabaApplication $application): static
    {
        $this->application = $application;
        return $this;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(?string $attachment): static
    {
        $this->attachment = $attachment;
        return $this;
    }

   public function getExamResult(): ?string
{
    return $this->examResult;
}

public function setExamResult(?string $examResult): static
{
    $this->examResult = $examResult;
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }
}
