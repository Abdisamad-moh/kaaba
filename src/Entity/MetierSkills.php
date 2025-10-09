<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\SkillsRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: SkillsRepository::class)]
class MetierSkills
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $career_name = null;

    /**
     * @var Collection<int, EmployerJobs>
     */
    #[ORM\ManyToMany(targetEntity: EmployerJobs::class, mappedBy: 'required_skill')]
    private Collection $employerJobs;

    /**
     * @var Collection<int, EmployerJobs>
     */
    #[ORM\ManyToMany(targetEntity: EmployerJobs::class, mappedBy: 'preferred_skill')]
    private Collection $empJobs;

    /**
     * @var Collection<int, JobseekerDetails>
     */
    #[ORM\ManyToMany(targetEntity: JobseekerDetails::class, mappedBy: 'skills')]
    private Collection $jobseekerDetails;

    /**
     * @var Collection<int, JobSeekerSkill>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerSkill::class, mappedBy: 'skill', orphanRemoval: true)]
    private Collection $jobSeekerSkills;

    /**
     * @var Collection<int, JobSeekerResume>
     */
    #[ORM\ManyToMany(targetEntity: JobSeekerResume::class, mappedBy: 'skills')]
    private Collection $jobSeekerResumes;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $custom = false;

    /**
     * @var Collection<int, MetierCareers>
     */
    #[ORM\ManyToMany(targetEntity: MetierCareers::class, mappedBy: 'skills')]
    private Collection $metierCareers;



    public function __construct()
    {
        $this->employerJobs = new ArrayCollection();
        $this->empJobs = new ArrayCollection();
        $this->jobseekerDetails = new ArrayCollection();
        $this->jobSeekerSkills = new ArrayCollection();
        $this->jobSeekerResumes = new ArrayCollection();
        $this->metierCareers = new ArrayCollection();
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

    public function getCareerName(): ?string
    {
        return $this->career_name;
    }

    public function setCareerName(string $career_name): static
    {
        $this->career_name = $career_name;

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
            $employerJob->addRequiredSkill($this);
        }

        return $this;
    }

    public function removeEmployerJob(EmployerJobs $employerJob): static
    {
        if ($this->employerJobs->removeElement($employerJob)) {
            $employerJob->removeRequiredSkill($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, EmployerJobs>
     */
    public function getEmpJobs(): Collection
    {
        return $this->empJobs;
    }

    public function addEmpJob(EmployerJobs $empJob): static
    {
        if (!$this->empJobs->contains($empJob)) {
            $this->empJobs->add($empJob);
            $empJob->addPreferredSkill($this);
        }

        return $this;
    }

    public function removeEmpJob(EmployerJobs $empJob): static
    {
        if ($this->empJobs->removeElement($empJob)) {
            $empJob->removePreferredSkill($this);
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
            $jobseekerDetail->addSkill($this);
        }

        return $this;
    }

    public function removeJobseekerDetail(JobseekerDetails $jobseekerDetail): static
    {
        if ($this->jobseekerDetails->removeElement($jobseekerDetail)) {
            $jobseekerDetail->removeSkill($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerSkill>
     */
    public function getJobSeekerSkills(): Collection
    {
        return $this->jobSeekerSkills;
    }

    public function addJobSeekerSkill(JobSeekerSkill $jobSeekerSkill): static
    {
        if (!$this->jobSeekerSkills->contains($jobSeekerSkill)) {
            $this->jobSeekerSkills->add($jobSeekerSkill);
            $jobSeekerSkill->setSkill($this);
        }

        return $this;
    }

    public function removeJobSeekerSkill(JobSeekerSkill $jobSeekerSkill): static
    {
        if ($this->jobSeekerSkills->removeElement($jobSeekerSkill)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerSkill->getSkill() === $this) {
                $jobSeekerSkill->setSkill(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobSeekerResume>
     */
    public function getJobSeekerResumes(): Collection
    {
        return $this->jobSeekerResumes;
    }

    public function addJobSeekerResume(JobSeekerResume $jobSeekerResume): static
    {
        if (!$this->jobSeekerResumes->contains($jobSeekerResume)) {
            $this->jobSeekerResumes->add($jobSeekerResume);
            $jobSeekerResume->addSkill($this);
        }

        return $this;
    }

    public function removeJobSeekerResume(JobSeekerResume $jobSeekerResume): static
    {
        if ($this->jobSeekerResumes->removeElement($jobSeekerResume)) {
            $jobSeekerResume->removeSkill($this);
        }

        return $this;
    }

    public function isCustom(): ?bool
    {
        return $this->custom;
    }

    public function setCustom(bool $custom): static
    {
        $this->custom = $custom;

        return $this;
    }

    /**
     * @return Collection<int, MetierCareers>
     */
    public function getMetierCareers(): Collection
    {
        return $this->metierCareers;
    }

    public function addMetierCareer(MetierCareers $metierCareer): static
    {
        if (!$this->metierCareers->contains($metierCareer)) {
            $this->metierCareers->add($metierCareer);
            $metierCareer->addSkill($this);
        }

        return $this;
    }

    public function removeMetierCareer(MetierCareers $metierCareer): static
    {
        if ($this->metierCareers->removeElement($metierCareer)) {
            $metierCareer->removeSkill($this);
        }

        return $this;
    }

}
