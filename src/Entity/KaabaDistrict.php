<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\KaabaDistrictRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: KaabaDistrictRepository::class)]
class KaabaDistrict
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
    #[ORM\OneToMany(targetEntity: KaabaApplication::class, mappedBy: 'district')]
    private Collection $kaabaApplications;

    #[ORM\ManyToOne(inversedBy: 'kaabaDistricts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?KaabaRegion $region = null;

    public function __construct()
    {
        $this->kaabaApplications = new ArrayCollection();
         $this->uuid = Uuid::v4();
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
            $kaabaApplication->setDistrict($this);
        }

        return $this;
    }

    public function removeKaabaApplication(KaabaApplication $kaabaApplication): static
    {
        if ($this->kaabaApplications->removeElement($kaabaApplication)) {
            // set the owning side to null (unless already changed)
            if ($kaabaApplication->getDistrict() === $this) {
                $kaabaApplication->setDistrict(null);
            }
        }

        return $this;
    }

    public function getRegion(): ?KaabaRegion
    {
        return $this->region;
    }

    public function setRegion(?KaabaRegion $region): static
    {
        $this->region = $region;

        return $this;
    }
}
