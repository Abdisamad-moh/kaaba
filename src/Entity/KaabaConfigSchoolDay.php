<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\KaabaConfigSchoolDayRepository;

#[ORM\Entity(repositoryClass: KaabaConfigSchoolDayRepository::class)]
class KaabaConfigSchoolDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $dayOfWeek = null; // e.g., 'Monday', 'Tuesday', etc.

    #[ORM\Column]
    private ?bool $isSchoolDay = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isHalfDay = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $dayType = null; // e.g., 'normal', 'exam', 'event', etc.

    #[ORM\Column(nullable: true)]
    private ?int $orderIndex = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayOfWeek(): ?string
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(string $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function isIsSchoolDay(): ?bool
    {
        return $this->isSchoolDay;
    }

    public function setIsSchoolDay(bool $isSchoolDay): static
    {
        $this->isSchoolDay = $isSchoolDay;

        return $this;
    }

    public function isIsHalfDay(): ?bool
    {
        return $this->isHalfDay;
    }

    public function setIsHalfDay(?bool $isHalfDay): static
    {
        $this->isHalfDay = $isHalfDay;

        return $this;
    }

    public function getDayType(): ?string
    {
        return $this->dayType;
    }

    public function setDayType(?string $dayType): static
    {
        $this->dayType = $dayType;

        return $this;
    }

    public function getOrderIndex(): ?int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(?int $orderIndex): static
    {
        $this->orderIndex = $orderIndex;

        return $this;
    }
}