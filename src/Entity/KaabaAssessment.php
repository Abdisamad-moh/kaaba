<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;


#[ORM\Entity]
#[ORM\Table(name: 'kaaba_assessments')]
class KaabaAssessment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'assessment')]
    #[ORM\JoinColumn(nullable: false)]
    private ?KaabaApplication $application = null;

    // Store all sections dynamically as JSON
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $motivation = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $household = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $income = [];

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $totalScore = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $recommendedStatus = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

      #[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;

    #[ORM\OneToMany(mappedBy: 'assessment', targetEntity: KaabaAssessmentLog::class)]
private Collection $assessmentLogs;

#[ORM\Column(length: 255, nullable: true)]
private ?string $interviewerName = null;
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
         $this->uuid = Uuid::v4();
          $this->assessmentLogs = new ArrayCollection();
    }

    public function getInterviewerName(): ?string
{
    return $this->interviewerName;
}

public function setInterviewerName(?string $interviewerName): static
{
    $this->interviewerName = $interviewerName;
    $this->updatedAt = new \DateTime();
    return $this;
}
    // Getters & Setters
 public function getUuid(): ?string
    {
        return $this->uuid;
    }
    public function getAssessmentLogs(): Collection
{
    return $this->assessmentLogs;
}
public function addAssessmentLog(KaabaAssessmentLog $log): static
{
    if (!$this->assessmentLogs->contains($log)) {
        $this->assessmentLogs->add($log);
        $log->setAssessment($this);   // Set owning side
    }

    return $this;
}
public function removeAssessmentLog(KaabaAssessmentLog $log): static
{
    if ($this->assessmentLogs->removeElement($log)) {
        if ($log->getAssessment() === $this) {
            $log->setAssessment(null);   // Remove owning side
        }
    }

    return $this;
}


    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplication(): ?KaabaApplication
    {
        return $this->application;
    }

    public function setApplication(?KaabaApplication $application): static
    {
        $this->application = $application;

        // Update the updatedAt timestamp
        $this->updatedAt = new \DateTime();

        return $this;
    }

    public function getMotivation(): ?array
    {
        return $this->motivation;
    }

    public function setMotivation(?array $motivation): static
    {
        $this->motivation = $motivation;
        $this->updatedAt = new \DateTime();
        
        return $this;
    }

    public function getHousehold(): ?array
    {
        return $this->household;
    }

    public function setHousehold(?array $household): static
    {
        $this->household = $household;
        $this->updatedAt = new \DateTime();
        
        return $this;
    }

    public function getIncome(): ?array
    {
        return $this->income;
    }

    public function setIncome(?array $income): static
    {
        $this->income = $income;
        $this->updatedAt = new \DateTime();
        
        return $this;
    }

    public function getTotalScore(): ?int
    {
        return $this->totalScore;
    }

    public function setTotalScore(?int $totalScore): static
    {
        $this->totalScore = $totalScore;
        $this->updatedAt = new \DateTime();
        
        return $this;
    }

    public function getRecommendedStatus(): ?string
    {
        return $this->recommendedStatus;
    }

    public function setRecommendedStatus(?string $recommendedStatus): static
    {
        $this->recommendedStatus = $recommendedStatus;
        $this->updatedAt = new \DateTime();
        
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        
        return $this;
    }

    // Helper methods for calculating and managing scores

    public function calculateTotalScore(): int
    {
        $total = 0;

        // Calculate motivation score
        if (!empty($this->motivation)) {
            $total += array_sum(array_column($this->motivation, 'score'));
        }

        // Calculate household score
        if (!empty($this->household)) {
            $total += array_sum(array_column($this->household, 'score'));
        }

        // Calculate income score
        if (!empty($this->income)) {
            $total += array_sum(array_column($this->income, 'score'));
        }

        $this->totalScore = $total;
        return $total;
    }

    public function getSectionScore(string $section): int
    {
        if (!in_array($section, ['motivation', 'household', 'income'])) {
            throw new \InvalidArgumentException('Invalid section. Must be one of: motivation, household, income');
        }

        $sectionData = $this->$section;
        if (empty($sectionData)) {
            return 0;
        }

        return array_sum($sectionData);
    }

    public function updateSection(string $section, array $data): static
    {
        if (!in_array($section, ['motivation', 'household', 'income'])) {
            throw new \InvalidArgumentException('Invalid section. Must be one of: motivation, household, income');
        }

        $this->$section = $data;
        $this->calculateTotalScore();
        $this->updatedAt = new \DateTime();

        return $this;
    }

    public function getAssessmentData(): array
    {
        return [
            'motivation' => $this->motivation ?? [],
            'household' => $this->household ?? [],
            'income' => $this->income ?? [],
            'totalScore' => $this->totalScore,
            'recommendedStatus' => $this->recommendedStatus,
        ];
    }

    public function __toString(): string
    {
        return sprintf('Assessment #%d for Application #%d', 
            $this->id ?? 0, 
            $this->application?->getId() ?? 0
        );
    }
}