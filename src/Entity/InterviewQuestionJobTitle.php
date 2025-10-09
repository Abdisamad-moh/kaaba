<?php

namespace App\Entity;

use App\Repository\InterviewQuestionJobTitleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterviewQuestionJobTitleRepository::class)]
class InterviewQuestionJobTitle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, InterviewQuestions>
     */
    #[ORM\OneToMany(targetEntity: InterviewQuestions::class, mappedBy: 'job_type')]
    private Collection $interviewQuestions;

    #[ORM\Column(nullable: true)]
    private ?bool $is_special = null;

    public function __construct()
    {
        $this->interviewQuestions = new ArrayCollection();
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
     * @return Collection<int, InterviewQuestions>
     */
    public function getInterviewQuestions(): Collection
    {
        return $this->interviewQuestions;
    }

    public function addInterviewQuestion(InterviewQuestions $interviewQuestion): static
    {
        if (!$this->interviewQuestions->contains($interviewQuestion)) {
            $this->interviewQuestions->add($interviewQuestion);
            $interviewQuestion->setJobType($this);
        }

        return $this;
    }

    public function removeInterviewQuestion(InterviewQuestions $interviewQuestion): static
    {
        if ($this->interviewQuestions->removeElement($interviewQuestion)) {
            // set the owning side to null (unless already changed)
            if ($interviewQuestion->getJobType() === $this) {
                $interviewQuestion->setJobType(null);
            }
        }

        return $this;
    }

    public function isSpecial(): ?bool
    {
        return $this->is_special;
    }

    public function setSpecial(?bool $is_special): static
    {
        $this->is_special = $is_special;

        return $this;
    }
}
