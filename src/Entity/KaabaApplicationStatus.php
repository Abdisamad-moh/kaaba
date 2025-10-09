<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\KaabaApplicationStatusRepository;

#[ORM\Entity(repositoryClass: KaabaApplicationStatusRepository::class)]
class KaabaApplicationStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;


    #[ORM\Column(length: 255)]
    private ?string $label = null;


#[ORM\OneToMany(mappedBy: 'status', targetEntity: KaabaApplication::class)]
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
    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

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
        $kaabaApplication->setStatus($this);
    }

    return $this;
}

public function removeKaabaApplication(KaabaApplication $kaabaApplication): static
{
    if ($this->kaabaApplications->removeElement($kaabaApplication)) {
        // set the owning side to null (unless already changed)
        if ($kaabaApplication->getStatus() === $this) {
            $kaabaApplication->setStatus(null);
        }
    }

    return $this;
}
}
