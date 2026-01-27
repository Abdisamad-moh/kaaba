<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\KaabaBiotimeAreaRepository;

#[ORM\Entity(repositoryClass: KaabaBiotimeAreaRepository::class)]
class KaabaBiotimeArea
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $area_id = null; // Biotime area ID

    #[ORM\Column(length: 255)]
    private ?string $area_name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $timezone = 'Africa/Dar_es_Salaam';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    /**
     * @var Collection<int, KaabaBiotimeDevice>
     */
    #[ORM\OneToMany(mappedBy: 'area', targetEntity: KaabaBiotimeDevice::class, cascade: ['persist', 'remove'])]
    private Collection $devices;

    #[ORM\OneToOne(inversedBy: 'biotimeArea', targetEntity: KaabaInstitute::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?KaabaInstitute $institute = null;

    #[ORM\ManyToOne(inversedBy: 'biotimeAreas')]
    #[ORM\JoinColumn(nullable: true)]
    private ?KaabaConfigSchoolHour $working_hours_config = null;

    #[ORM\ManyToOne(inversedBy: 'biotimeAreas')]
    #[ORM\JoinColumn(nullable: true)]
    private ?KaabaConfigSchoolHoliday $holiday_config = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $biotime_api_key = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $biotime_server_url = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->devices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAreaId(): ?string
    {
        return $this->area_id;
    }

    public function setAreaId(string $area_id): static
    {
        $this->area_id = $area_id;

        return $this;
    }

    public function getAreaName(): ?string
    {
        return $this->area_name;
    }

    public function setAreaName(string $area_name): static
    {
        $this->area_name = $area_name;

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

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return Collection<int, KaabaBiotimeDevice>
     */
    public function getDevices(): Collection
    {
        return $this->devices;
    }

    public function addDevice(KaabaBiotimeDevice $device): static
    {
        if (!$this->devices->contains($device)) {
            $this->devices->add($device);
            $device->setArea($this);
        }

        return $this;
    }

    public function removeDevice(KaabaBiotimeDevice $device): static
    {
        if ($this->devices->removeElement($device)) {
            // set the owning side to null (unless already changed)
            if ($device->getArea() === $this) {
                $device->setArea(null);
            }
        }

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

    public function getWorkingHoursConfig(): ?KaabaConfigSchoolHour
    {
        return $this->working_hours_config;
    }

    public function setWorkingHoursConfig(?KaabaConfigSchoolHour $working_hours_config): static
    {
        $this->working_hours_config = $working_hours_config;

        return $this;
    }

    public function getHolidayConfig(): ?KaabaConfigSchoolHoliday
    {
        return $this->holiday_config;
    }

    public function setHolidayConfig(?KaabaConfigSchoolHoliday $holiday_config): static
    {
        $this->holiday_config = $holiday_config;

        return $this;
    }

    public function getBiotimeApiKey(): ?string
    {
        return $this->biotime_api_key;
    }

    public function setBiotimeApiKey(?string $biotime_api_key): static
    {
        $this->biotime_api_key = $biotime_api_key;

        return $this;
    }

    public function getBiotimeServerUrl(): ?string
    {
        return $this->biotime_server_url;
    }

    public function setBiotimeServerUrl(?string $biotime_server_url): static
    {
        $this->biotime_server_url = $biotime_server_url;

        return $this;
    }
}