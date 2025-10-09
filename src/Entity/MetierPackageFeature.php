<?php

namespace App\Entity;

use App\Repository\MetierPackageFeatureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierPackageFeatureRepository::class)]
class MetierPackageFeature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;



    #[ORM\Column(length: 255)]
    private ?string $action = null;

    #[ORM\Column]
    private ?bool $status = null;



    /**
     * @var Collection<int, MetierPackagePlan>
     */
    #[ORM\OneToMany(targetEntity: MetierPackagePlan::class, mappedBy: 'feature')]
    private Collection $metierPackagePlans;

  

    public function __construct()
    {
        $this->metierPackagePlans = new ArrayCollection();
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


    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }



    /**
     * @return Collection<int, MetierPackagePlan>
     */
    public function getMetierPackagePlans(): Collection
    {
        return $this->metierPackagePlans;
    }

    public function addMetierPackagePlan(MetierPackagePlan $metierPackagePlan): static
    {
        if (!$this->metierPackagePlans->contains($metierPackagePlan)) {
            $this->metierPackagePlans->add($metierPackagePlan);
            $metierPackagePlan->setFeature($this);
        }

        return $this;
    }

    public function removeMetierPackagePlan(MetierPackagePlan $metierPackagePlan): static
    {
        if ($this->metierPackagePlans->removeElement($metierPackagePlan)) {
            // set the owning side to null (unless already changed)
            if ($metierPackagePlan->getFeature() === $this) {
                $metierPackagePlan->setFeature(null);
            }
        }

        return $this;
    }

  
}
