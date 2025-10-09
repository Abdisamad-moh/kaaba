<?php

namespace App\Entity;

use App\Repository\JobSeekerPopularSearchRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobSeekerPopularSearchRepository::class)]
class JobSeekerPopularSearch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
