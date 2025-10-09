<?php

namespace App\Entity;

use App\Repository\MetierJobIndustryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierJobIndustryRepository::class)]
class MetierJobIndustry
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
    #[ORM\OneToMany(targetEntity: EmployerJobs::class, mappedBy: 'industry')]
    private Collection $employerJobs;

    /**
     * @var Collection<int, EmployerDetails>
     */
    #[ORM\OneToMany(targetEntity: EmployerDetails::class, mappedBy: 'industry')]
    private Collection $employers;

    public function __construct()
    {
        $this->employerJobs = new ArrayCollection();
        $this->employers = new ArrayCollection();
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
            $employerJob->setIndustry($this);
        }

        return $this;
    }

    public function removeEmployerJob(EmployerJobs $employerJob): static
    {
        if ($this->employerJobs->removeElement($employerJob)) {
            // set the owning side to null (unless already changed)
            if ($employerJob->getIndustry() === $this) {
                $employerJob->setIndustry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EmployerDetails>
     */
    public function getEmployers(): Collection
    {
        return $this->employers;
    }

    public function addEmployer(EmployerDetails $employer): static
    {
        if (!$this->employers->contains($employer)) {
            $this->employers->add($employer);
            $employer->setIndustry($this);
        }

        return $this;
    }

    public function removeEmployer(EmployerDetails $employer): static
    {
        if ($this->employers->removeElement($employer)) {
            // set the owning side to null (unless already changed)
            if ($employer->getIndustry() === $this) {
                $employer->setIndustry(null);
            }
        }

        return $this;
    }
}
