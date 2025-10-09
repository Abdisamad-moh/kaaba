<?php

namespace App\Entity;

use App\Repository\KaabaGenderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KaabaGenderRepository::class)]
class KaabaGender
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, KaabaApplication>
     */
    #[ORM\OneToMany(targetEntity: KaabaApplication::class, mappedBy: 'gender', orphanRemoval: true)]
    private Collection $kaabaApplications;

    public function __construct()
    {
        $this->kaabaApplications = new ArrayCollection();
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
            $kaabaApplication->setGender($this);
        }

        return $this;
    }

    public function removeKaabaApplication(KaabaApplication $kaabaApplication): static
    {
        if ($this->kaabaApplications->removeElement($kaabaApplication)) {
            // set the owning side to null (unless already changed)
            if ($kaabaApplication->getGender() === $this) {
                $kaabaApplication->setGender(null);
            }
        }

        return $this;
    }
}
