<?php

namespace App\Entity;

use App\Repository\JobSeekerJobAlertRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobSeekerJobAlertRepository::class)]
class JobSeekerJobAlert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'jobalerts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $jobseeker = null;

    /**
     * @var Collection<int, MetierJobCategory>
     */
    #[ORM\ManyToMany(targetEntity: MetierJobCategory::class, inversedBy: 'jobSeekerJobAlerts')]
    private Collection $jobcategory;

    #[ORM\Column(nullable: true)]
    private ?int $phone = null;

    public function __construct()
    {
        $this->jobcategory = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobseeker(): ?User
    {
        return $this->jobseeker;
    }

    public function setJobseeker(?User $jobseeker): static
    {
        $this->jobseeker = $jobseeker;

        return $this;
    }

    /**
     * @return Collection<int, MetierJobCategory>
     */
    public function getJobcategory(): Collection
    {
        return $this->jobcategory;
    }

    public function addJobcategory(MetierJobCategory $jobcategory): static
    {
        if (!$this->jobcategory->contains($jobcategory)) {
            $this->jobcategory->add($jobcategory);
        }

        return $this;
    }

    public function removeJobcategory(MetierJobCategory $jobcategory): static
    {
        $this->jobcategory->removeElement($jobcategory);

        return $this;
    }

    public function getPhone(): ?int
    {
        return $this->phone;
    }

    public function setPhone(?int $phone): static
    {
        $this->phone = $phone;

        return $this;
    }
}
