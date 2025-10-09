<?php

namespace App\Entity;

use App\Repository\MetierSubscriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
#[HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: MetierSubscriptionRepository::class)]
class MetierSubscription
{
    use Timestamps;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column]
    private ?int $display_order = null;

    #[ORM\Column]
    private ?bool $status = null;

    /**
     * @var Collection<int, MetierSubscriptionFeature>
     */
    #[ORM\OneToMany(targetEntity: MetierSubscriptionFeature::class, mappedBy: 'plan', orphanRemoval: true)]
    private Collection $metierSubscriptionFeatures;

    public function __construct()
    {
        $this->metierSubscriptionFeatures = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
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

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

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

    /**
     * @return Collection<int, MetierSubscriptionFeature>
     */
    public function getMetierSubscriptionFeatures(): Collection
    {
        return $this->metierSubscriptionFeatures;
    }

    public function addMetierSubscriptionFeature(MetierSubscriptionFeature $metierSubscriptionFeature): static
    {
        if (!$this->metierSubscriptionFeatures->contains($metierSubscriptionFeature)) {
            $this->metierSubscriptionFeatures->add($metierSubscriptionFeature);
            $metierSubscriptionFeature->setPlan($this);
        }

        return $this;
    }

    public function removeMetierSubscriptionFeature(MetierSubscriptionFeature $metierSubscriptionFeature): static
    {
        if ($this->metierSubscriptionFeatures->removeElement($metierSubscriptionFeature)) {
            // set the owning side to null (unless already changed)
            if ($metierSubscriptionFeature->getPlan() === $this) {
                $metierSubscriptionFeature->setPlan(null);
            }
        }

        return $this;
    }
}
