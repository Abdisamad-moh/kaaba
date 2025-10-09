<?php

namespace App\Entity;

use App\Repository\CourseApplicationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseApplicationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CourseApplication
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'courseApplications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $applicant = null;

    #[ORM\ManyToOne(inversedBy: 'courseApplications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EmployerCourses $course = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplicant(): ?User
    {
        return $this->applicant;
    }

    public function setApplicant(?User $applicant): static
    {
        $this->applicant = $applicant;

        return $this;
    }

    public function getCourse(): ?EmployerCourses
    {
        return $this->course;
    }

    public function setCourse(?EmployerCourses $course): static
    {
        $this->course = $course;

        return $this;
    }
}
