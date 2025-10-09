<?php

namespace App\Entity;

use App\Repository\MetierCurrencyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity(repositoryClass: MetierCurrencyRepository::class)]
#[HasLifecycleCallbacks]
class MetierCurrency
{
    use Timestamps;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $symbol = null;

    /**
     * @var Collection<int, EmployerJobs>
     */
    #[ORM\OneToMany(targetEntity: EmployerJobs::class, mappedBy: 'currency')]
    private Collection $employerJobs;

    /**
     * @var Collection<int, JobSeekerExperience>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerExperience::class, mappedBy: 'currency')]
    private Collection $jobSeekerExperiences;

    public function __construct()
    {
        $this->employerJobs = new ArrayCollection();
        $this->jobSeekerExperiences = new ArrayCollection();
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(?string $symbol): static
    {
        $this->symbol = $symbol;

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
            $employerJob->setCurrency($this);
        }

        return $this;
    }

    public function removeEmployerJob(EmployerJobs $employerJob): static
    {
        if ($this->employerJobs->removeElement($employerJob)) {
            // set the owning side to null (unless already changed)
            if ($employerJob->getCurrency() === $this) {
                $employerJob->setCurrency(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerExperience>
     */
    public function getJobSeekerExperiences(): Collection
    {
        return $this->jobSeekerExperiences;
    }

    public function addJobSeekerExperience(JobSeekerExperience $jobSeekerExperience): static
    {
        if (!$this->jobSeekerExperiences->contains($jobSeekerExperience)) {
            $this->jobSeekerExperiences->add($jobSeekerExperience);
            $jobSeekerExperience->setCurrency($this);
        }

        return $this;
    }

    public function removeJobSeekerExperience(JobSeekerExperience $jobSeekerExperience): static
    {
        if ($this->jobSeekerExperiences->removeElement($jobSeekerExperience)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerExperience->getCurrency() === $this) {
                $jobSeekerExperience->setCurrency(null);
            }
        }

        return $this;
    }
}
