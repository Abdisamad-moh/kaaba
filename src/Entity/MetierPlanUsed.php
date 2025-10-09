<?php

namespace App\Entity;

use App\Repository\MetierPlanUsedRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierPlanUsedRepository::class)]
class MetierPlanUsed
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'metierPlanUseds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierPackages $plan = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255)]
    private ?string $balance = null;

    #[ORM\ManyToOne(inversedBy: 'metierPlanUseds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierOrder $subscription = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPlan(): ?MetierPackages
    {
        return $this->plan;
    }

    public function setPlan(?MetierPackages $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    public function getSubscription(): ?MetierOrder
    {
        return $this->subscription;
    }

    public function setSubscription(?MetierOrder $subscription): static
    {
        $this->subscription = $subscription;

        return $this;
    }
}
