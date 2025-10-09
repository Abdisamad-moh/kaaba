<?php

namespace App\Entity;

use App\Repository\MetierOrderPaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierOrderPaymentRepository::class)]
class MetierOrderPayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $payment_category = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $payment_date = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $received_from = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $payment_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    private ?MetierOrder $purchase = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    

    public function getPaymentCategory(): ?string
    {
        return $this->payment_category;
    }

    public function setPaymentCategory(string $payment_category): static
    {
        $this->payment_category = $payment_category;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->payment_date;
    }

    public function setPaymentDate(\DateTimeInterface $payment_date): static
    {
        $this->payment_date = $payment_date;

        return $this;
    }

    public function getReceivedFrom(): ?User
    {
        return $this->received_from;
    }

    public function setReceivedFrom(?User $received_from): static
    {
        $this->received_from = $received_from;

        return $this;
    }

    public function getPaymentId(): ?string
    {
        return $this->payment_id;
    }

    public function setPaymentId(?string $payment_id): static
    {
        $this->payment_id = $payment_id;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

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

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }
}
