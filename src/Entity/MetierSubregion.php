<?php

namespace App\Entity;

use App\Repository\MetierSubregionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierSubregionRepository::class)]
class MetierSubregion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $translations = null;

    #[ORM\ManyToOne(inversedBy: 'metierSubregions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierRegion $region = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wikiDataId = null;

    /**
     * @var Collection<int, MetierCountry>
     */
    #[ORM\OneToMany(targetEntity: MetierCountry::class, mappedBy: 'subregion_id')]
    private Collection $metierCountries;

    public function __construct()
    {
        $this->metierCountries = new ArrayCollection();
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

    public function setTranslations(?string $translations): static
    {
        $this->translations = $translations;

        return $this;
    }

    public function getRegion(): ?MetierRegion
    {
        return $this->region;
    }

    public function setRegion(?MetierRegion $region): static
    {
        $this->region = $region;

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
}
