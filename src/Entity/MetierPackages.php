<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\MetierPackagePlan;
use Doctrine\Common\Collections\Collection;
use App\Repository\MetierPackagesRepository;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: MetierPackagesRepository::class)]
class MetierPackages
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $cost = null;

    #[ORM\Column]
    private ?bool $status = null;



    #[ORM\Column(nullable: true)]
    private ?int $duration = null;

    /**
     * @var Collection<int, MetierPackagePlan>
     */
    #[ORM\OneToMany(targetEntity: MetierPackagePlan::class, mappedBy: 'package', orphanRemoval: true)]
    private Collection $metierPackagePlans;

    /**
     * @var Collection<int, MetierOrder>
     */
    #[ORM\OneToMany(targetEntity: MetierOrder::class, mappedBy: 'plan')]
    private Collection $orders;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $file = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $class = null;

    #[ORM\Column(nullable: true)]
    private ?bool $discount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $old_price = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_offer = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $offer_end_date = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_special = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $special_message = null;

    /**
     * @var Collection<int, MetierPlanUsed>
     */
    #[ORM\OneToMany(targetEntity: MetierPlanUsed::class, mappedBy: 'plan', orphanRemoval: true)]
    private Collection $metierPlanUseds;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $job_balance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $course_balance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tender_balance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $durationCategory = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_popular = null;


    public function __construct()
    {
        $this->metierPackagePlans = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->metierPlanUseds = new ArrayCollection();
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

    public function setDescription(string $description): static
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

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(string $cost): static
    {
        $this->cost = $cost;

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


    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;

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
            $metierPackagePlan->setPackage($this);
        }

        return $this;
    }

    public function removeMetierPackagePlan(MetierPackagePlan $metierPackagePlan): static
    {
        if ($this->metierPackagePlans->removeElement($metierPackagePlan)) {
            // set the owning side to null (unless already changed)
            if ($metierPackagePlan->getPackage() === $this) {
                $metierPackagePlan->setPackage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MetierOrder>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(MetierOrder $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setPlan($this);
        }

        return $this;
    }

    public function removeOrder(MetierOrder $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getPlan() === $this) {
                $order->setPlan(null);
            }
        }

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

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(?string $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): static
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(?string $class): static
    {
        $this->class = $class;

        return $this;
    }

    public function isDiscount(): ?bool
    {
        return $this->discount;
    }

    public function setDiscount(?bool $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getOldPrice(): ?string
    {
        return $this->old_price;
    }

    public function setOldPrice(?string $old_price): static
    {
        $this->old_price = $old_price;

        return $this;
    }

    public function isOffer(): ?bool
    {
        return $this->is_offer;
    }

    public function setOffer(?bool $is_offer): static
    {
        $this->is_offer = $is_offer;

        return $this;
    }

    public function getOfferEndDate(): ?\DateTimeInterface
    {
        return $this->offer_end_date;
    }

    public function setOfferEndDate(?\DateTimeInterface $offer_end_date): static
    {
        $this->offer_end_date = $offer_end_date;

        return $this;
    }

    public function isSpecial(): ?bool
    {
        return $this->is_special;
    }

    public function setSpecial(?bool $is_special): static
    {
        $this->is_special = $is_special;

        return $this;
    }

    public function getSpecialMessage(): ?string
    {
        return $this->special_message;
    }

    public function setSpecialMessage(?string $special_message): static
    {
        $this->special_message = $special_message;

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
            $metierPlanUsed->setPlan($this);
        }

        return $this;
    }

    public function removeMetierPlanUsed(MetierPlanUsed $metierPlanUsed): static
    {
        if ($this->metierPlanUseds->removeElement($metierPlanUsed)) {
            // set the owning side to null (unless already changed)
            if ($metierPlanUsed->getPlan() === $this) {
                $metierPlanUsed->setPlan(null);
            }
        }

        return $this;
    }

    public function getJobBalance(): ?string
    {
        return $this->job_balance;
    }

    public function setJobBalance(string $job_balance): static
    {
        $this->job_balance = $job_balance;

        return $this;
    }

    public function getCourseBalance(): ?string
    {
        return $this->course_balance;
    }

    public function setCourseBalance(?string $course_balance): static
    {
        $this->course_balance = $course_balance;

        return $this;
    }

    public function getTenderBalance(): ?string
    {
        return $this->tender_balance;
    }

    public function setTenderBalance(?string $tender_balance): static
    {
        $this->tender_balance = $tender_balance;

        return $this;
    }

    public function getDurationCategory(): ?string
    {
        return $this->durationCategory;
    }

    public function setDurationCategory(?string $durationCategory): static
    {
        $this->durationCategory = $durationCategory;

        return $this;
    }

    public function isPopular(): ?bool
    {
        return $this->is_popular;
    }

    public function setPopular(?bool $is_popular): static
    {
        $this->is_popular = $is_popular;

        return $this;
    }

    
}
