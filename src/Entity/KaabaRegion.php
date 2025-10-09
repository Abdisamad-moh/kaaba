<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\KaabaRegionRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: KaabaRegionRepository::class)]
class KaabaRegion
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
    #[ORM\OneToMany(targetEntity: KaabaApplication::class, mappedBy: 'region', orphanRemoval: true)]
    private Collection $kaabaApplications;

    /**
     * @var Collection<int, KaabaApplication>
     */
    #[ORM\OneToMany(targetEntity: KaabaApplication::class, mappedBy: 'secondary_region', orphanRemoval: true)]
    private Collection $kaabaApplicationsSchools;

    /**
     * @var Collection<int, KaabaDistrict>
     */
    #[ORM\OneToMany(targetEntity: KaabaDistrict::class, mappedBy: 'region')]
    private Collection $kaabaDistricts;

    public function __construct()
    {
        $this->kaabaApplications = new ArrayCollection();
        $this->kaabaApplicationsSchools = new ArrayCollection();
        $this->uuid = Uuid::v4();
        $this->kaabaDistricts = new ArrayCollection();
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
            $kaabaApplication->setRegion($this);
        }

        return $this;
    }

    public function removeKaabaApplication(KaabaApplication $kaabaApplication): static
    {
        if ($this->kaabaApplications->removeElement($kaabaApplication)) {
            // set the owning side to null (unless already changed)
            if ($kaabaApplication->getRegion() === $this) {
                $kaabaApplication->setRegion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, KaabaApplication>
     */
    public function getKaabaApplicationsSchools(): Collection
    {
        return $this->kaabaApplicationsSchools;
    }

    public function addKaabaApplicationsSchool(KaabaApplication $kaabaApplicationsSchool): static
    {
        if (!$this->kaabaApplicationsSchools->contains($kaabaApplicationsSchool)) {
            $this->kaabaApplicationsSchools->add($kaabaApplicationsSchool);
            $kaabaApplicationsSchool->setSecondaryRegion($this);
        }

        return $this;
    }

    public function removeKaabaApplicationsSchool(KaabaApplication $kaabaApplicationsSchool): static
    {
        if ($this->kaabaApplicationsSchools->removeElement($kaabaApplicationsSchool)) {
            // set the owning side to null (unless already changed)
            if ($kaabaApplicationsSchool->getSecondaryRegion() === $this) {
                $kaabaApplicationsSchool->setSecondaryRegion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, KaabaDistrict>
     */
    public function getKaabaDistricts(): Collection
    {
        return $this->kaabaDistricts;
    }

    public function addKaabaDistrict(KaabaDistrict $kaabaDistrict): static
    {
        if (!$this->kaabaDistricts->contains($kaabaDistrict)) {
            $this->kaabaDistricts->add($kaabaDistrict);
            $kaabaDistrict->setRegion($this);
        }

        return $this;
    }

    public function removeKaabaDistrict(KaabaDistrict $kaabaDistrict): static
    {
        if ($this->kaabaDistricts->removeElement($kaabaDistrict)) {
            // set the owning side to null (unless already changed)
            if ($kaabaDistrict->getRegion() === $this) {
                $kaabaDistrict->setRegion(null);
            }
        }

        return $this;
    }
}
