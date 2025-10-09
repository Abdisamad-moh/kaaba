<?php

namespace App\Entity;

use App\Repository\JobSeekerResumeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobSeekerResumeRepository::class)]
class JobSeekerResume
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'jobSeekerResume', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobSeeker = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $education = null;

    /**
     * @var Collection<int, MetierSkills>
     */
    #[ORM\ManyToMany(targetEntity: MetierSkills::class, inversedBy: 'jobSeekerResumes')]
    private Collection $skills;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $experience = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkedin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $portfolio = null;

    #[ORM\Column(nullable: true)]
    private ?bool $willingToRelocate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sreport = null;

    #[ORM\ManyToOne(inversedBy: 'jobSeekerResumes')]
    private ?MetierCareers $jobTitle = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $publicProfile = true;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $resumeVisible = true;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobSeeker(): ?User
    {
        return $this->jobSeeker;
    }

    public function setJobSeeker(User $jobSeeker): static
    {
        $this->jobSeeker = $jobSeeker;

        return $this;
    }

    /**
     * @return Collection<int, MetierCareers>
     */


    public function getEducation(): ?string
    {
        return $this->education;
    }

    public function setEducation(?string $education): static
    {
        $this->education = $education;

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

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(?string $experience): static
    {
        $this->experience = $experience;

        return $this;
    }

    public function getLinkedin(): ?string
    {
        return $this->linkedin;
    }

    public function setLinkedin(?string $linkedin): static
    {
        $this->linkedin = $linkedin;

        return $this;
    }

    public function getPortfolio(): ?string
    {
        return $this->portfolio;
    }

    public function setPortfolio(?string $portfolio): static
    {
        $this->portfolio = $portfolio;

        return $this;
    }

    public function isWillingToRelocate(): ?bool
    {
        return $this->willingToRelocate;
    }

    public function setWillingToRelocate(?bool $willingToRelocate): static
    {
        $this->willingToRelocate = $willingToRelocate;

        return $this;
    }

    public function getSreport(): ?string
    {
        return $this->sreport;
    }

    public function setSreport(?string $sreport): static
    {
        $this->sreport = $sreport;

        return $this;
    }

    public function getJobTitle(): ?MetierCareers
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?MetierCareers $jobTitle): static
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    public function isPublicProfile(): ?bool
    {
        return $this->publicProfile;
    }

    public function setPublicProfile(bool $publicProfile): static
    {
        $this->publicProfile = $publicProfile;

        return $this;
    }

    public function isResumeVisible(): ?bool
    {
        return $this->resumeVisible;
    }

    public function setResumeVisible(bool $resumeVisible): static
    {
        $this->resumeVisible = $resumeVisible;

        return $this;
    }
}
