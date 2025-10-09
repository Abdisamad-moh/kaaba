<?php

namespace App\Entity;

use App\Repository\MetierServiceOrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierServiceOrderRepository::class)]
class MetierServiceOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customer = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'metierServiceOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierOrder $purchase = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?string
    {
        return $this->customer;
    }

    public function setCustomer(?string $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPurchase(): ?MetierOrder
    {
        return $this->purchase;
    }

    public function setPurchase(?MetierOrder $purchase): static
    {
        $this->purchase = $purchase;

        return $this;
    }
}
