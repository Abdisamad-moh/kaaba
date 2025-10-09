<?php

namespace App\Entity;

use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\MetierOrderRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: MetierOrderRepository::class)]
class MetierOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?MetierPackages $plan = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $customer = null;

    #[ORM\Column(length: 255)]
    private ?string $payment_status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $order_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $valid_from = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $valid_to = null;

    #[ORM\Column(length: 255)]
    private ?string $customer_type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    /**
     * @var Collection<int, MetierOrderPayment>
     */
    #[ORM\OneToMany(targetEntity: MetierOrderPayment::class, mappedBy: 'purchase')]
    private Collection $payments;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $order_uid = null;

    /**
     * @var Collection<int, MetierDownloads>
     */
    #[ORM\OneToMany(targetEntity: MetierDownloads::class, mappedBy: 'purchase')]
    private Collection $metierDownloads;

    /**
     * @var Collection<int, MetierServiceOrder>
     */
    #[ORM\OneToMany(targetEntity: MetierServiceOrder::class, mappedBy: 'purchase', orphanRemoval: true)]
    private Collection $metierServiceOrders;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?bool $canceled = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $tax = null;

    /**
     * @var Collection<int, MetierDownloadable>
     */
    #[ORM\OneToMany(targetEntity: MetierDownloadable::class, mappedBy: 'purchase', orphanRemoval: true)]
    private Collection $downloadables;

    /**
     * @var Collection<int, MetierPlanUsed>
     */
    #[ORM\OneToMany(targetEntity: MetierPlanUsed::class, mappedBy: 'subscription')]
    private Collection $metierPlanUseds;

    // /**
    //  * @ORM\PrePersist
    //  */
    // public function generateOrderUid()
    // {
    //     $this->order_uid =  // Generates a unique string with prefix 'ord_'
    // }

    public function __construct()
    {
        $this->payments = new ArrayCollection();
        $this->metierDownloads = new ArrayCollection();
        $this->metierServiceOrders = new ArrayCollection();
        $this->downloadables = new ArrayCollection();
        $this->metierPlanUseds = new ArrayCollection();
    }

    public function setValidityPeriod(int $duration, string $periodType = 'monthly'): void
    {
        $now = new DateTimeImmutable();
        $this->valid_from = $now;
        if ($periodType === 'weekly') {
           $this->valid_to = $now->add(new DateInterval('P1W'));
        } else {
            // Default to monthly
            $this->valid_to = $now->add(new DateInterval('P' . $duration . 'M'));
        }
    }
    public function getId(): ?int
    {
        return $this->id;
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

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }
    public function isValid(): bool
    {
        $now = new DateTimeImmutable();

        return $this->valid_from !== null &&
            $this->valid_to !== null &&
            $this->valid_from <= $now &&
            $now <= $this->valid_to;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->payment_status;
    }

    public function setPaymentStatus(string $payment_status): static
    {
        $this->payment_status = $payment_status;

        return $this;
    }

    public function getOrderDate(): ?\DateTimeInterface
    {
        return $this->order_date;
    }

    public function setOrderDate(\DateTimeInterface $order_date): static
    {
        $this->order_date = $order_date;

        return $this;
    }

    public function getValidFrom(): ?\DateTimeInterface
    {
        return $this->valid_from;
    }

    public function setValidFrom(?\DateTimeInterface $valid_from): static
    {
        $this->valid_from = $valid_from;

        return $this;
    }

    public function getValidTo(): ?\DateTimeInterface
    {
        return $this->valid_to;
    }

    public function setValidTo(?\DateTimeInterface $valid_to): static
    {
        $this->valid_to = $valid_to;

        return $this;
    }

    public function getCustomerType(): ?string
    {
        return $this->customer_type;
    }

    public function setCustomerType(string $customer_type): static
    {
        $this->customer_type = $customer_type;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, MetierOrderPayment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(MetierOrderPayment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setPurchase($this);
        }

        return $this;
    }

    public function removePayment(MetierOrderPayment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getPurchase() === $this) {
                $payment->setPurchase(null);
            }
        }

        return $this;
    }

    public function getOrderUid(): ?string
    {
        return $this->order_uid;
    }

    public function setOrderUid(string $order_uid): static
    {
        $this->order_uid = $order_uid;

        return $this;
    }

    /**
     * @return Collection<int, MetierDownloads>
     */
    public function getMetierDownloads(): Collection
    {
        return $this->metierDownloads;
    }

    public function addMetierDownload(MetierDownloads $metierDownload): static
    {
        if (!$this->metierDownloads->contains($metierDownload)) {
            $this->metierDownloads->add($metierDownload);
            $metierDownload->setPurchase($this);
        }

        return $this;
    }

    public function removeMetierDownload(MetierDownloads $metierDownload): static
    {
        if ($this->metierDownloads->removeElement($metierDownload)) {
            // set the owning side to null (unless already changed)
            if ($metierDownload->getPurchase() === $this) {
                $metierDownload->setPurchase(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierServiceOrder>
     */
    public function getMetierServiceOrders(): Collection
    {
        return $this->metierServiceOrders;
    }

    public function addMetierServiceOrder(MetierServiceOrder $metierServiceOrder): static
    {
        if (!$this->metierServiceOrders->contains($metierServiceOrder)) {
            $this->metierServiceOrders->add($metierServiceOrder);
            $metierServiceOrder->setPurchase($this);
        }

        return $this;
    }

    public function removeMetierServiceOrder(MetierServiceOrder $metierServiceOrder): static
    {
        if ($this->metierServiceOrders->removeElement($metierServiceOrder)) {
            // set the owning side to null (unless already changed)
            if ($metierServiceOrder->getPurchase() === $this) {
                $metierServiceOrder->setPurchase(null);
            }
        }

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isCanceled(): ?bool
    {
        return $this->canceled;
    }

    public function setCanceled(?bool $canceled): static
    {
        $this->canceled = $canceled;

        return $this;
    }

    public function getTax(): ?string
    {
        return $this->tax;
    }

    public function setTax(?string $tax): static
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * @return Collection<int, MetierDownloadable>
     */
    public function getDownloadables(): Collection
    {
        return $this->downloadables;
    }

    public function addDownloadable(MetierDownloadable $downloadable): static
    {
        if (!$this->downloadables->contains($downloadable)) {
            $this->downloadables->add($downloadable);
            $downloadable->setPurchase($this);
        }

        return $this;
    }

    public function removeDownloadable(MetierDownloadable $downloadable): static
    {
        if ($this->downloadables->removeElement($downloadable)) {
            // set the owning side to null (unless already changed)
            if ($downloadable->getPurchase() === $this) {
                $downloadable->setPurchase(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierPlanUsed>
     */
    public function getMetierPlanUseds(): Collection
    {
        return $this->metierPlanUseds;
    }

    public function addMetierPlanUsed(MetierPlanUsed $metierPlanUsed): static
    {
        if (!$this->metierPlanUseds->contains($metierPlanUsed)) {
            $this->metierPlanUseds->add($metierPlanUsed);
            $metierPlanUsed->setSubscription($this);
        }

        return $this;
    }

    public function removeMetierPlanUsed(MetierPlanUsed $metierPlanUsed): static
    {
        if ($this->metierPlanUseds->removeElement($metierPlanUsed)) {
            // set the owning side to null (unless already changed)
            if ($metierPlanUsed->getSubscription() === $this) {
                $metierPlanUsed->setSubscription(null);
            }
        }

        return $this;
    }
}
