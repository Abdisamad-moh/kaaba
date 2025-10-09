<?php

namespace App\Entity;

use App\Repository\MetierSubscriptionFeatureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierSubscriptionFeatureRepository::class)]
class MetierSubscriptionFeature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $display_order = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\ManyToOne(inversedBy: 'metierSubscriptionFeatures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierSubscription $plan = null;

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

    public function getDisplayOrder(): ?int
    {
        return $this->display_order;
    }

    public function setDisplayOrder(int $display_order): static
    {
        $this->display_order = $display_order;

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

    public function getPlan(): ?MetierSubscription
    {
        return $this->plan;
    }

    public function setPlan(?MetierSubscription $plan): static
    {
        $this->plan = $plan;

        return $this;
    }
}
