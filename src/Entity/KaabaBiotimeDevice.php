<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\KaabaBiotimeDeviceRepository;

#[ORM\Entity(repositoryClass: KaabaBiotimeDeviceRepository::class)]
class KaabaBiotimeDevice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $device_id = null; // Biotime device ID

    #[ORM\Column(length: 255)]
    private ?string $device_name = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $device_type = null; // biometric, card, facial, etc.

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serial_number = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $ip_address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null; // Physical location description

    #[ORM\Column(length: 20)]
    private ?string $status = 'active'; // active, inactive, maintenance

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $last_sync_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\ManyToOne(inversedBy: 'devices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?KaabaBiotimeArea $area = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(nullable: true)]
    private ?int $port = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeviceId(): ?string
    {
        return $this->device_id;
    }

    public function setDeviceId(string $device_id): static
    {
        $this->device_id = $device_id;

        return $this;
    }

    public function getDeviceName(): ?string
    {
        return $this->device_name;
    }

    public function setDeviceName(string $device_name): static
    {
        $this->device_name = $device_name;

        return $this;
    }

    public function getDeviceType(): ?string
    {
        return $this->device_type;
    }

    public function setDeviceType(?string $device_type): static
    {
        $this->device_type = $device_type;

        return $this;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serial_number;
    }

    public function setSerialNumber(?string $serial_number): static
    {
        $this->serial_number = $serial_number;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function setIpAddress(?string $ip_address): static
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

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

    public function getLastSyncDate(): ?\DateTimeInterface
    {
        return $this->last_sync_date;
    }

    public function setLastSyncDate(?\DateTimeInterface $last_sync_date): static
    {
        $this->last_sync_date = $last_sync_date;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getArea(): ?KaabaBiotimeArea
    {
        return $this->area;
    }

    public function setArea(?KaabaBiotimeArea $area): static
    {
        $this->area = $area;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): static
    {
        $this->port = $port;

        return $this;
    }
}