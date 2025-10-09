<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Repository\KaabaQualificationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: KaabaQualificationRepository::class)]
class KaabaQualification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;

      #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, KaabaApplication>
     */
    #[ORM\OneToMany(targetEntity: KaabaApplication::class, mappedBy: 'highest_qualification')]
    private Collection $kaabaApplications;

   

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

    /**
     * @return Collection<int, KaabaApplication>
     */
    public function getKaabaApplications(): Collection
    {
        return $this->kaabaApplications;
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

    public function addKaabaApplication(KaabaApplication $kaabaApplication): static
    {
        if (!$this->kaabaApplications->contains($kaabaApplication)) {
            $this->kaabaApplications->add($kaabaApplication);
            $kaabaApplication->setHighestQualification($this);
        }

        return $this;
    }

    public function removeKaabaApplication(KaabaApplication $kaabaApplication): static
    {
        if ($this->kaabaApplications->removeElement($kaabaApplication)) {
            // set the owning side to null (unless already changed)
            if ($kaabaApplication->getHighestQualification() === $this) {
                $kaabaApplication->setHighestQualification(null);
            }
        }

        return $this;
    }

   
}
