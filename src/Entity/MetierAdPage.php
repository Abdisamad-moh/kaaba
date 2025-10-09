<?php

namespace App\Entity;

use App\Repository\MetierAdPageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierAdPageRepository::class)]
class MetierAdPage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

   

    /**
     * @var Collection<int, MetierAds>
     */
    #[ORM\ManyToMany(targetEntity: MetierAds::class, mappedBy: 'pages')]
    private Collection $metierAds;

    public function __construct()
    {
        $this->metierAds = new ArrayCollection();
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
     * @return Collection<int, MetierAds>
     */
    public function getMetierAds(): Collection
    {
        return $this->metierAds;
    }

    public function addMetierAd(MetierAds $metierAd): static
    {
        if (!$this->metierAds->contains($metierAd)) {
            $this->metierAds->add($metierAd);
            $metierAd->addPage($this);
        }

        return $this;
    }

    public function removeMetierAd(MetierAds $metierAd): static
    {
        if ($this->metierAds->removeElement($metierAd)) {
            $metierAd->removePage($this);
        }

        return $this;
    }
}
