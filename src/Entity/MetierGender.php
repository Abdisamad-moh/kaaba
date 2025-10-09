<?php

namespace App\Entity;

use App\Repository\MetierGenderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierGenderRepository::class)]
class MetierGender
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Name = null;

    /**
     * @var Collection<int, JobseekerDetails>
     */
    #[ORM\OneToMany(targetEntity: JobseekerDetails::class, mappedBy: 'gender')]
    private Collection $jobseekerDetails;

    public function __construct()
    {
        $this->jobseekerDetails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->Name;
    }

    public function setName(?string $Name): static
    {
        $this->Name = $Name;

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
            $jobseekerDetail->setGender($this);
        }

        return $this;
    }

    public function removeJobseekerDetail(JobseekerDetails $jobseekerDetail): static
    {
        if ($this->jobseekerDetails->removeElement($jobseekerDetail)) {
            // set the owning side to null (unless already changed)
            if ($jobseekerDetail->getGender() === $this) {
                $jobseekerDetail->setGender(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->Name;
    }
}
