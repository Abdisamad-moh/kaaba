<?php

namespace App\Entity;

use App\Repository\MetierLanguageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierLanguageRepository::class)]
class MetierLanguage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, JobSeekerLanguage>
     */
    #[ORM\OneToMany(targetEntity: JobSeekerLanguage::class, mappedBy: 'language', orphanRemoval: true)]
    private Collection $jobSeekerLanguages;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shortName = null;

    public function __construct()
    {
        $this->jobSeekerLanguages = new ArrayCollection();
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
     * @return Collection<int, JobSeekerLanguage>
     */
    public function getJobSeekerLanguages(): Collection
    {
        return $this->jobSeekerLanguages;
    }

    public function addJobSeekerLanguage(JobSeekerLanguage $jobSeekerLanguage): static
    {
        if (!$this->jobSeekerLanguages->contains($jobSeekerLanguage)) {
            $this->jobSeekerLanguages->add($jobSeekerLanguage);
            $jobSeekerLanguage->setLanguage($this);
        }

        return $this;
    }

    public function removeJobSeekerLanguage(JobSeekerLanguage $jobSeekerLanguage): static
    {
        if ($this->jobSeekerLanguages->removeElement($jobSeekerLanguage)) {
            // set the owning side to null (unless already changed)
            if ($jobSeekerLanguage->getLanguage() === $this) {
                $jobSeekerLanguage->setLanguage(null);
            }
        }

        return $this;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortName(?string $shortName): static
    {
        $this->shortName = $shortName;

        return $this;
    }
}
