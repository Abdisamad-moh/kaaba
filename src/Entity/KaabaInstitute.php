<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Repository\KaabaInstituteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: KaabaInstituteRepository::class)]
class KaabaInstitute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;

    /**
     * @var Collection<int, KaabaApplication>
     */
    #[ORM\OneToMany(targetEntity: KaabaApplication::class, mappedBy: 'institute', orphanRemoval: true)]
    private Collection $kaabaApplications;

    /**
     * @var Collection<int, KaabaCourse>
     */
    #[ORM\OneToMany(targetEntity: KaabaCourse::class, mappedBy: 'institute')]
    private Collection $kaabaCourses;


    #[ORM\ManyToOne(inversedBy: 'institutes')]
    #[ORM\JoinColumn(nullable: true)] // Institute MUST belong to a scholarship
    private ?KaabaScholarship $scholarship = null;

    #[ORM\ManyToOne(inversedBy: 'kaabaInstitutes')]
    private ?User $manager = null;

    #[ORM\OneToMany(mappedBy: 'highest_qualification', targetEntity: KaabaApplicationDuplicate::class)]
    private Collection $kaabaApplicationDuplicates;

    #[ORM\OneToOne(mappedBy: 'institute', targetEntity: KaabaConfigSchoolHour::class, cascade: ['persist', 'remove'])]
    private ?KaabaConfigSchoolHour $schoolHoursConfig = null;

    #[ORM\OneToOne(mappedBy: 'institute', targetEntity: KaabaBiotimeArea::class, cascade: ['persist', 'remove'])]
private ?KaabaBiotimeArea $biotimeArea = null;

    /**
     * @var Collection<int, KaabaAttendance>
     */
    #[ORM\OneToMany(targetEntity: KaabaAttendance::class, mappedBy: 'institute')]
    private Collection $attendances;

    // Add this property
#[ORM\OneToMany(mappedBy: 'institute', targetEntity: KaabaStudentExcuse::class)]
private Collection $excuses;


    public function __construct()
    {
        $this->kaabaApplications = new ArrayCollection();
        $this->uuid = Uuid::v4();
        $this->kaabaCourses = new ArrayCollection();
        $this->kaabaApplicationDuplicates = new ArrayCollection();
        $this->attendances = new ArrayCollection();
        $this->excuses = new ArrayCollection();
    }

    // Add these methods
public function getExcuses(): Collection
{
    return $this->excuses;
}

public function addExcuse(KaabaStudentExcuse $excuse): static
{
    if (!$this->excuses->contains($excuse)) {
        $this->excuses->add($excuse);
        $excuse->setInstitute($this);
    }
    return $this;
}

public function removeExcuse(KaabaStudentExcuse $excuse): static
{
    if ($this->excuses->removeElement($excuse)) {
        if ($excuse->getInstitute() === $this) {
            $excuse->setInstitute(null);
        }
    }
    return $this;
}

    // Add getter and setter methods
public function getBiotimeArea(): ?KaabaBiotimeArea
{
    return $this->biotimeArea;
}

public function setBiotimeArea(?KaabaBiotimeArea $biotimeArea): static
{
    // Unset the owning side of the relation if necessary
    if ($biotimeArea === null && $this->biotimeArea !== null) {
        $this->biotimeArea->setInstitute(null);
    }

    // Set the owning side of the relation if necessary
    if ($biotimeArea !== null && $biotimeArea->getInstitute() !== $this) {
        $biotimeArea->setInstitute($this);
    }

    $this->biotimeArea = $biotimeArea;

    return $this;
}

     public function getSchoolHoursConfig(): ?KaabaConfigSchoolHour
    {
        return $this->schoolHoursConfig;
    }

    public function setSchoolHoursConfig(?KaabaConfigSchoolHour $schoolHoursConfig): static
    {
        // Unset the owning side of the relation if necessary
        if ($schoolHoursConfig === null && $this->schoolHoursConfig !== null) {
            $this->schoolHoursConfig->setInstitute(null);
        }

        // Set the owning side of the relation if necessary
        if ($schoolHoursConfig !== null && $schoolHoursConfig->getInstitute() !== $this) {
            $schoolHoursConfig->setInstitute($this);
        }

        $this->schoolHoursConfig = $schoolHoursConfig;

        return $this;
    }

    // Add getters and setters
    public function getKaabaApplicationDuplicates(): Collection
    {
        return $this->kaabaApplicationDuplicates;
    }

    public function addKaabaApplicationDuplicate(KaabaApplicationDuplicate $kaabaApplicationDuplicate): static
    {
        if (!$this->kaabaApplicationDuplicates->contains($kaabaApplicationDuplicate)) {
            $this->kaabaApplicationDuplicates->add($kaabaApplicationDuplicate);
            $kaabaApplicationDuplicate->setHighestQualification($this);
        }

        return $this;
    }

    public function removeKaabaApplicationDuplicate(KaabaApplicationDuplicate $kaabaApplicationDuplicate): static
    {
        if ($this->kaabaApplicationDuplicates->removeElement($kaabaApplicationDuplicate)) {
            // set the owning side to null (unless already changed)
            if ($kaabaApplicationDuplicate->getHighestQualification() === $this) {
                $kaabaApplicationDuplicate->setHighestQualification(null);
            }
        }

        return $this;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
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
     * @return Collection<int, KaabaApplication>
     */
    public function getKaabaApplications(): Collection
    {
        return $this->kaabaApplications;
    }

    public function addKaabaApplication(KaabaApplication $kaabaApplication): static
    {
        if (!$this->kaabaApplications->contains($kaabaApplication)) {
            $this->kaabaApplications->add($kaabaApplication);
            $kaabaApplication->setInstitute($this);
        }

        return $this;
    }

    public function removeKaabaApplication(KaabaApplication $kaabaApplication): static
    {
        if ($this->kaabaApplications->removeElement($kaabaApplication)) {
            // set the owning side to null (unless already changed)
            if ($kaabaApplication->getInstitute() === $this) {
                $kaabaApplication->setInstitute(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, KaabaCourse>
     */
    public function getKaabaCourses(): Collection
    {
        return $this->kaabaCourses;
    }

    public function addKaabaCourse(KaabaCourse $kaabaCourse): static
    {
        if (!$this->kaabaCourses->contains($kaabaCourse)) {
            $this->kaabaCourses->add($kaabaCourse);
            $kaabaCourse->setInstitute($this);
        }

        return $this;
    }

    public function removeKaabaCourse(KaabaCourse $kaabaCourse): static
    {
        if ($this->kaabaCourses->removeElement($kaabaCourse)) {
            // set the owning side to null (unless already changed)
            if ($kaabaCourse->getInstitute() === $this) {
                $kaabaCourse->setInstitute(null);
            }
        }

        return $this;
    }


    public function getScholarship(): ?KaabaScholarship
    {
        return $this->scholarship;
    }

    public function setScholarship(?KaabaScholarship $scholarship): static
    {
        $this->scholarship = $scholarship;

        return $this;
    }

    public function getManager(): ?User
    {
        return $this->manager;
    }

    public function setManager(?User $manager): static
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * @return Collection<int, KaabaAttendance>
     */
    public function getAttendances(): Collection
    {
        return $this->attendances;
    }

    public function addAttendance(KaabaAttendance $attendance): static
    {
        if (!$this->attendances->contains($attendance)) {
            $this->attendances->add($attendance);
            $attendance->setInstitute($this);
        }

        return $this;
    }

    public function removeAttendance(KaabaAttendance $attendance): static
    {
        if ($this->attendances->removeElement($attendance)) {
            // set the owning side to null (unless already changed)
            if ($attendance->getInstitute() === $this) {
                $attendance->setInstitute(null);
            }
        }

        return $this;
    }
}
