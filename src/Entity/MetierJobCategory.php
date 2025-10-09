<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Repository\MetierJobCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MetierJobCategoryRepository::class)]
class MetierJobCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['job_list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['job_list'])]
    private ?string $name = null;

    /**
     * @var Collection<int, EmployerJobs>
     */
    #[ORM\OneToMany(targetEntity: EmployerJobs::class, mappedBy: 'job_category')]
    private Collection $employerJobs;

    /**
     * @var Collection<int, JobSeekerJobAlert>
     */
    #[ORM\ManyToMany(targetEntity: JobSeekerJobAlert::class, mappedBy: 'jobcategory')]
    private Collection $jobSeekerJobAlerts;

    public function __construct()
    {
        $this->employerJobs = new ArrayCollection();
        $this->jobSeekerJobAlerts = new ArrayCollection();
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
            $employerJob->setJobCategory($this);
        }

        return $this;
    }

    public function removeEmployerJob(EmployerJobs $employerJob): static
    {
        if ($this->employerJobs->removeElement($employerJob)) {
            // set the owning side to null (unless already changed)
            if ($employerJob->getJobCategory() === $this) {
                $employerJob->setJobCategory(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerJobAlert>
     */
    public function getJobSeekerJobAlerts(): Collection
    {
        return $this->jobSeekerJobAlerts;
    }

    public function addJobSeekerJobAlert(JobSeekerJobAlert $jobSeekerJobAlert): static
    {
        if (!$this->jobSeekerJobAlerts->contains($jobSeekerJobAlert)) {
            $this->jobSeekerJobAlerts->add($jobSeekerJobAlert);
            $jobSeekerJobAlert->addJobcategory($this);
        }

        return $this;
    }

    public function removeJobSeekerJobAlert(JobSeekerJobAlert $jobSeekerJobAlert): static
    {
        if ($this->jobSeekerJobAlerts->removeElement($jobSeekerJobAlert)) {
            $jobSeekerJobAlert->removeJobcategory($this);
        }

        return $this;
    }
}
