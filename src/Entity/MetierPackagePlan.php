<?php

namespace App\Entity;

use App\Repository\MetierPackagePlanRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierPackagePlanRepository::class)]
class MetierPackagePlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'metierPackagePlans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierPackages $package = null;

    #[ORM\ManyToOne(inversedBy: 'metierPackagePlans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierPackageFeature $feature = null;

    #[ORM\Column]
    private ?bool $inclusive = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPackage(): ?MetierPackages
    {
        return $this->package;
    }

    public function setPackage(?MetierPackages $package): static
    {
        $this->package = $package;

        return $this;
    }

    public function getFeature(): ?MetierPackageFeature
    {
        return $this->feature;
    }

    public function setFeature(?MetierPackageFeature $feature): static
    {
        $this->feature = $feature;

        return $this;
    }

    public function isInclusive(): ?bool
    {
        return $this->inclusive;
    }

    public function setInclusive(bool $inclusive): static
    {
        $this->inclusive = $inclusive;

        return $this;
    }
}
