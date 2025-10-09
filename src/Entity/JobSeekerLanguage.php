<?php

namespace App\Entity;

use App\Repository\JobSeekerLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobSeekerLanguageRepository::class)]
class JobSeekerLanguage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerLanguages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobSeeker = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerLanguages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierLanguage $language = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $proficiency = null;

    #[ORM\Column(nullable: true)]
    private ?bool $reading = null;

    #[ORM\Column(nullable: true)]
    private ?bool $writing = null;

    #[ORM\Column(nullable: true)]
    private ?bool $speaking = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobSeeker(): ?User
    {
        return $this->jobSeeker;
    }

    public function setJobSeeker(?User $jobSeeker): static
    {
        $this->jobSeeker = $jobSeeker;

        return $this;
    }

    public function getLanguage(): ?MetierLanguage
    {
        return $this->language;
    }

    public function setLanguage(?MetierLanguage $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getProficiency(): ?string
    {
        return $this->proficiency;
    }

    public function setProficiency(?string $proficiency): static
    {
        $this->proficiency = $proficiency;

        return $this;
    }

    public function isReading(): ?bool
    {
        return $this->reading;
    }

    public function setReading(?bool $reading): static
    {
        $this->reading = $reading;

        return $this;
    }

    public function isWriting(): ?bool
    {
        return $this->writing;
    }

    public function setWriting(bool $writing): static
    {
        $this->writing = $writing;

        return $this;
    }

    public function isSpeaking(): ?bool
    {
        return $this->speaking;
    }

    public function setSpeaking(?bool $speaking): static
    {
        $this->speaking = $speaking;

        return $this;
    }

    public function getSkills()
    {
        $skills = [];
        if($this->isReading()) array_push($skills, 'Read');
        if($this->isWriting()) array_push($skills, 'Write');
        if($this->isSpeaking()) array_push($skills, 'Speak');
        return $skills;
    }
}
