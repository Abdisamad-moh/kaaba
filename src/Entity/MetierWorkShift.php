<?php

namespace App\Entity;

use App\Repository\MetierWorkShiftRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierWorkShiftRepository::class)]
class MetierWorkShift
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, EmployerJobs>
     */
    #[ORM\ManyToMany(targetEntity: EmployerJobs::class, mappedBy: 'shift')]
    private Collection $employerJobs;

    public function __construct()
    {
        $this->employerJobs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, EmployerJobs>
     */
    public function getEmployerJobs(): Collection
    {
        return $this->employerJobs;
    }

    public function addEmployerJob(EmployerJobs $employerJob): static
    {
        if (!$this->employerJobs->contains($employerJob)) {
            $this->employerJobs->add($employerJob);
            $employerJob->addShift($this);
        }

        return $this;
    }

    public function removeEmployerJob(EmployerJobs $employerJob): static
    {
        if ($this->employerJobs->removeElement($employerJob)) {
            $employerJob->removeShift($this);
        }

        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }
}
