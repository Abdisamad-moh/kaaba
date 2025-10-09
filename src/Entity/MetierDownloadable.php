<?php

namespace App\Entity;

use App\Repository\MetierDownloadableRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierDownloadableRepository::class)]
class MetierDownloadable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'downloadables')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private ?bool $has_downloaded = null;

    #[ORM\ManyToOne(inversedBy: 'downloadables')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MetierOrder $purchase = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $expiration_date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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

    public function hasDownloaded(): ?bool
    {
        return $this->has_downloaded;
    }

    public function setHasDownloaded(bool $has_downloaded): static
    {
        $this->has_downloaded = $has_downloaded;

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

    public function getExpirationDate(): ?\DateTimeInterface
    {
        return $this->expiration_date;
    }

    public function setExpirationDate(\DateTimeInterface $expiration_date): static
    {
        $this->expiration_date = $expiration_date;

        return $this;
    }
}
