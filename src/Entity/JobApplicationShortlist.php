<?php

namespace App\Entity;

use App\Repository\JobApplicationShortlistRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: JobApplicationShortlistRepository::class)]
class JobApplicationShortlist
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'automaticShortlists')]
    #[ORM\JoinColumn(nullable: false)]
    private ?JobApplication $application = null;

    #[ORM\Column]
    private ?float $score = null;

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

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(float $score): static
    {
        $this->score = $score;

        return $this;
    }
}
