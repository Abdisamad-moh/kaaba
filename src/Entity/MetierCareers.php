<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\CareersRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CareersRepository::class)]
class MetierCareers
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
    #[ORM\OneToMany(targetEntity: EmployerJobs::class, mappedBy: 'jobtitle')]
    private Collection $employerJobs;

    /**
     * @var Collection<int, JobseekerDetails>
     */
    #[ORM\OneToMany(targetEntity: JobseekerDetails::class, mappedBy: 'profession')]
    private Collection $jobseekerDetails;

    /**
     * @var Collection<int, JobSeekerWork>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerWork::class, mappedBy: 'profession')]
    private Collection $jobSeekerWorks;

    /**
     * @var Collection<int, MetierSkills>
     */
    #[ORM\ManyToMany(targetEntity: MetierSkills::class, inversedBy: 'metierCareers')]
    private Collection $skills;

    /**
     * @var Collection<int, JobSeekerResume>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerResume::class, mappedBy: 'jobTitle')]
    private Collection $jobSeekerResumes;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->employerJobs = new ArrayCollection();
        $this->jobseekerDetails = new ArrayCollection();
        $this->jobSeekerWorks = new ArrayCollection();
        $this->jobSeekerResumes = new ArrayCollection();
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
            $employerJob->setJobtitle($this);
        }

        return $this;
    }

    public function removeEmployerJob(EmployerJobs $employerJob): static
    {
        if ($this->employerJobs->removeElement($employerJob)) {
            // set the owning side to null (unless already changed)
            if ($employerJob->getJobtitle() === $this) {
                $employerJob->setJobtitle(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobseekerDetails>
     */
    public function getJobseekerDetails(): Collection
    {
        return $this->jobseekerDetails;
    }

    public function addJobseekerDetail(JobseekerDetails $jobseekerDetail): static
    {
        if (!$this->jobseekerDetails->contains($jobseekerDetail)) {
            $this->jobseekerDetails->add($jobseekerDetail);
            $jobseekerDetail->setProfession($this);
        }

        return $this;
    }

    public function removeJobseekerDetail(JobseekerDetails $jobseekerDetail): static
    {
        if ($this->jobseekerDetails->removeElement($jobseekerDetail)) {
            // set the owning side to null (unless already changed)
            if ($jobseekerDetail->getProfession() === $this) {
                $jobseekerDetail->setProfession(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerWork>
     */
    public function getJobSeekerWorks(): Collection
    {
        return $this->jobSeekerWorks;
    }

    public function addJobSeekerWork(JobSeekerWork $jobSeekerWork): static
    {
        if (!$this->jobSeekerWorks->contains($jobSeekerWork)) {
            $this->jobSeekerWorks->add($jobSeekerWork);
            $jobSeekerWork->setProfession($this);
        }

        return $this;
    }

    public function removeJobSeekerWork(JobSeekerWork $jobSeekerWork): static
    {
        if ($this->jobSeekerWorks->removeElement($jobSeekerWork)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerWork->getProfession() === $this) {
                $jobSeekerWork->setProfession(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierSkills>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(MetierSkills $skill): static
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(MetierSkills $skill): static
    {
        $this->skills->removeElement($skill);

        return $this;
    }

    // /**
    //  * @return Collection<int, JobSeekerResume>
    //  */
    // public function getJobSeekerResumes(): Collection
    // {
    //     return $this->jobSeekerResumes;
    // }

    // public function addJobSeekerResume(JobSeekerResume $jobSeekerResume): static
    // {
    //     if (!$this->jobSeekerResumes->contains($jobSeekerResume)) {
    //         $this->jobSeekerResumes->add($jobSeekerResume);
    //         $jobSeekerResume->setJobTitle($this);
    //     }

    //     return $this;
    // }

    // public function removeJobSeekerResume(JobSeekerResume $jobSeekerResume): static
    // {
    //     if ($this->jobSeekerResumes->removeElement($jobSeekerResume)) {
    //         // set the owning side to null (unless already changed)
    //         if ($jobSeekerResume->getJobTitle() === $this) {
    //             $jobSeekerResume->setJobTitle(null);
    //         }
    //     }

    //     return $this;
    // }
}
