<?php

namespace App\Entity;

use App\Repository\KaabaKaabaApplicationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KaabaKaabaApplicationRepository::class)]
class KaabaKaabaApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'kaabaKaabaApplications')]
    private ?KaabaCourse $course = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourse(): ?KaabaCourse
    {
        return $this->course;
    }

    public function setCourse(?KaabaCourse $course): static
    {
        $this->course = $course;

        return $this;
    }
}
