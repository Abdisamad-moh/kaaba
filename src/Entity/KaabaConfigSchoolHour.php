<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\KaabaConfigSchoolHourRepository;

#[ORM\Entity(repositoryClass: KaabaConfigSchoolHourRepository::class)]
class KaabaConfigSchoolHour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $hoursPerDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxHoursPerDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $minHoursPerDay = null;

    #[ORM\OneToOne(inversedBy: 'schoolHoursConfig', targetEntity: KaabaInstitute::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?KaabaInstitute $institute = null;

    #[ORM\OneToMany(mappedBy: 'working_hours_config', targetEntity: KaabaBiotimeArea::class)]
private Collection $biotimeAreas;

   public function __construct()
    {
        $this->biotimeAreas = new ArrayCollection();
    }

    public function getBiotimeAreas(): Collection
{
    return $this->biotimeAreas;
}

public function addBiotimeArea(KaabaBiotimeArea $biotimeArea): static
{
    if (!$this->biotimeAreas->contains($biotimeArea)) {
        $this->biotimeAreas->add($biotimeArea);
        $biotimeArea->setWorkingHoursConfig($this);
    }

    return $this;
}

public function removeBiotimeArea(KaabaBiotimeArea $biotimeArea): static
{
    if ($this->biotimeAreas->removeElement($biotimeArea)) {
        // set the owning side to null (unless already changed)
        if ($biotimeArea->getWorkingHoursConfig() === $this) {
            $biotimeArea->setWorkingHoursConfig(null);
        }
    }

    return $this;
}


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHoursPerDay(): ?int
    {
        return $this->hoursPerDay;
    }

    public function setHoursPerDay(int $hoursPerDay): static
    {
        $this->hoursPerDay = $hoursPerDay;

        return $this;
    }

    public function getMaxHoursPerDay(): ?int
    {
        return $this->maxHoursPerDay;
    }

    public function setMaxHoursPerDay(?int $maxHoursPerDay): static
    {
        $this->maxHoursPerDay = $maxHoursPerDay;

        return $this;
    }

    public function getMinHoursPerDay(): ?int
    {
        return $this->minHoursPerDay;
    }

    public function setMinHoursPerDay(?int $minHoursPerDay): static
    {
        $this->minHoursPerDay = $minHoursPerDay;

        return $this;
    }

    public function getInstitute(): ?KaabaInstitute
    {
        return $this->institute;
    }

    public function setInstitute(?KaabaInstitute $institute): static
    {
        $this->institute = $institute;

        return $this;
    }
}