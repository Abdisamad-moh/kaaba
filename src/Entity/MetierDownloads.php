<?php

namespace App\Entity;

use App\Repository\MetierDownloadsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierDownloadsRepository::class)]
class MetierDownloads
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'metierDownloads')]
    private ?MetierOrder $purchase = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $file = null;

    #[ORM\ManyToOne(inversedBy: 'downloads')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $client = null;

    #[ORM\Column]
    private ?bool $is_downloadable = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

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

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(?string $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function isDownloadable(): ?bool
    {
        return $this->is_downloadable;
    }

    public function setDownloadable(bool $is_downloadable): static
    {
        $this->is_downloadable = $is_downloadable;

        return $this;
    }
}
