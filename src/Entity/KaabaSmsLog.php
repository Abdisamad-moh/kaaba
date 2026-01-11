<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\KaabaSmsLogRepository;

#[ORM\Entity(repositoryClass: KaabaSmsLogRepository::class)]

class KaabaSmsLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: KaabaApplication::class)]
    private ?KaabaApplication $application = null;

    #[ORM\Column(length: 255)]
    private ?string $receiverName = null;

    #[ORM\Column(length: 255)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $filteredStatuses = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $filteredInstitutes = null;

    #[ORM\Column(length: 100)]
    private ?string $messageStatus = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $gatewayResponse = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(inversedBy: 'kaabaSmsLogs')]
    private ?User $created_by = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getApplication(): ?KaabaApplication { return $this->application; }
    public function setApplication(?KaabaApplication $app): static { $this->application = $app; return $this; }

    public function getReceiverName(): ?string { return $this->receiverName; }
    public function setReceiverName(string $name): static { $this->receiverName = $name; return $this; }

    public function getPhoneNumber(): ?string { return $this->phoneNumber; }
    public function setPhoneNumber(string $phone): static { $this->phoneNumber = $phone; return $this; }

    public function getMessage(): ?string { return $this->message; }
    public function setMessage(string $msg): static { $this->message = $msg; return $this; }

    public function getFilteredStatuses(): ?array { return $this->filteredStatuses; }
    public function setFilteredStatuses(?array $statuses): static { $this->filteredStatuses = $statuses; return $this; }

    public function getFilteredInstitutes(): ?array { return $this->filteredInstitutes; }
    public function setFilteredInstitutes(?array $institutes): static { $this->filteredInstitutes = $institutes; return $this; }

    public function getMessageStatus(): ?string { return $this->messageStatus; }
    public function setMessageStatus(string $s): static { $this->messageStatus = $s; return $this; }

    public function getGatewayResponse(): ?string { return $this->gatewayResponse; }
    public function setGatewayResponse(?string $r): static { $this->gatewayResponse = $r; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }

    public function getCreatedBy(): ?User
    {
        return $this->created_by;
    }

    public function setCreatedBy(?User $created_by): static
    {
        $this->created_by = $created_by;

        return $this;
    }
}
