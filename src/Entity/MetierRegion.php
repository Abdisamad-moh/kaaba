<?php

namespace App\Entity;

use App\Repository\MetierRegionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierRegionRepository::class)]
class MetierRegion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $translations = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $wikiDataId = null;

    /**
     * @var Collection<int, MetierCountry>
     */
    #[ORM\OneToMany(targetEntity: MetierCountry::class, mappedBy: 'region_id')]
    private Collection $metierCountries;

    /**
     * @var Collection<int, MetierSubregion>
     */
    #[ORM\OneToMany(targetEntity: MetierSubregion::class, mappedBy: 'region')]
    private Collection $metierSubregions;

    public function __construct()
    {
        $this->metierCountries = new ArrayCollection();
        $this->metierSubregions = new ArrayCollection();
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

    public function getTranslations(): ?string
    {
        return $this->translations;
    }

    public function setTranslations(string $translations): static
    {
        $this->translations = $translations;

        return $this;
    }

    public function getWikiDataId(): ?string
    {
        return $this->wikiDataId;
    }

    public function setWikiDataId(?string $wikiDataId): static
    {
        $this->wikiDataId = $wikiDataId;

        return $this;
    }

    /**
     * @return Collection<int, MetierCountry>
     */
    public function getMetierCountries(): Collection
    {
        return $this->metierCountries;
    }

    public function addMetierCountry(MetierCountry $metierCountry): static
    {
        if (!$this->metierCountries->contains($metierCountry)) {
            $this->metierCountries->add($metierCountry);
            $metierCountry->setRegionId($this);
        }

        return $this;
    }

    public function removeMetierCountry(MetierCountry $metierCountry): static
    {
        if ($this->metierCountries->removeElement($metierCountry)) {
            // set the owning side to null (unless already changed)
            if ($metierCountry->getRegionId() === $this) {
                $metierCountry->setRegionId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierSubregion>
     */
    public function getMetierSubregions(): Collection
    {
        return $this->metierSubregions;
    }

    public function addMetierSubregion(MetierSubregion $metierSubregion): static
    {
        if (!$this->metierSubregions->contains($metierSubregion)) {
            $this->metierSubregions->add($metierSubregion);
            $metierSubregion->setRegion($this);
        }

        return $this;
    }

    public function removeMetierSubregion(MetierSubregion $metierSubregion): static
    {
        if ($this->metierSubregions->removeElement($metierSubregion)) {
            // set the owning side to null (unless already changed)
            if ($metierSubregion->getRegion() === $this) {
                $metierSubregion->setRegion(null);
            }
        }

        return $this;
    }
}
