<?php
// src/Entity/KaabaApplicationLog.php

namespace App\Entity;

use App\Repository\KaabaApplicationLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KaabaApplicationLogRepository::class)]
class KaabaApplicationLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'logs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?KaabaApplication $application = null;

    #[ORM\Column(length: 50)]
    private ?string $action = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null; // If you want to track which admin made changes

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

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

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    // ✅ FIX: Change method name to match the property with underscore
    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    // ✅ FIX: Change method name to match the property with underscore
    public function setCreated_at(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }


    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}