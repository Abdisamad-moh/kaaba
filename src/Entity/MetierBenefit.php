<?php

namespace App\Entity;

use App\Repository\MetierBenefitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierBenefitRepository::class)]
class MetierBenefit
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
    #[ORM\ManyToMany(targetEntity: EmployerJobs::class, mappedBy: 'benefits')]
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
            $employerJob->addBenefit($this);
        }

        return $this;
    }

    public function removeEmployerJob(EmployerJobs $employerJob): static
    {
        if ($this->employerJobs->removeElement($employerJob)) {
            $employerJob->removeBenefit($this);
        }

        return $this;
    }
}
