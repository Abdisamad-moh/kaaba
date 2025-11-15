<?php

namespace App\Entity;

use App\Repository\KaabaNationalityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KaabaNationalityRepository::class)]
class KaabaNationality
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
    #[ORM\OneToMany(targetEntity: KaabaApplication::class, mappedBy: 'nationality')]
    private Collection $kaabaApplications;

    #[ORM\OneToMany(mappedBy: 'nationality', targetEntity: KaabaApplicationDuplicate::class)]
private Collection $kaabaApplicationDuplicates;

    public function __construct()
    {
        $this->kaabaApplications = new ArrayCollection();
            $this->kaabaApplicationDuplicates = new ArrayCollection();
    }

    public function getKaabaApplicationDuplicates(): Collection
{
    return $this->kaabaApplicationDuplicates;
}

public function addKaabaApplicationDuplicate(KaabaApplicationDuplicate $kaabaApplicationDuplicate): static
{
    if (!$this->kaabaApplicationDuplicates->contains($kaabaApplicationDuplicate)) {
        $this->kaabaApplicationDuplicates->add($kaabaApplicationDuplicate);
        $kaabaApplicationDuplicate->setNationality($this);
    }

    return $this;
}

public function removeKaabaApplicationDuplicate(KaabaApplicationDuplicate $kaabaApplicationDuplicate): static
{
    if ($this->kaabaApplicationDuplicates->removeElement($kaabaApplicationDuplicate)) {
        // set the owning side to null (unless already changed)
        if ($kaabaApplicationDuplicate->getNationality() === $this) {
            $kaabaApplicationDuplicate->setNationality(null);
        }
    }

    return $this;
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
            $kaabaApplication->setNationality($this);
        }

        return $this;
    }

    public function removeKaabaApplication(KaabaApplication $kaabaApplication): static
    {
        if ($this->kaabaApplications->removeElement($kaabaApplication)) {
            // set the owning side to null (unless already changed)
            if ($kaabaApplication->getNationality() === $this) {
                $kaabaApplication->setNationality(null);
            }
        }

        return $this;
    }
}
