<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\KaabaConfigSchoolHolidayRepository;

#[ORM\Entity(repositoryClass: KaabaConfigSchoolHolidayRepository::class)]
class KaabaConfigSchoolHoliday
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column]
    private ?bool $isRecurring = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $holidayType = null; // e.g., 'public', 'religious', 'academic', 'national'

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\OneToMany(mappedBy: 'holiday_config', targetEntity: KaabaBiotimeArea::class)]
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
        $biotimeArea->setHolidayConfig($this);
    }

    return $this;
}

public function removeBiotimeArea(KaabaBiotimeArea $biotimeArea): static
{
    if ($this->biotimeAreas->removeElement($biotimeArea)) {
        // set the owning side to null (unless already changed)
        if ($biotimeArea->getHolidayConfig() === $this) {
            $biotimeArea->setHolidayConfig(null);
        }
    }

    return $this;
}
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function isIsRecurring(): ?bool
    {
        return $this->isRecurring;
    }

    public function setIsRecurring(bool $isRecurring): static
    {
        $this->isRecurring = $isRecurring;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getHolidayType(): ?string
    {
        return $this->holidayType;
    }

    public function setHolidayType(?string $holidayType): static
    {
        $this->holidayType = $holidayType;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }
}