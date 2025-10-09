<?php

namespace App\Entity;

use App\Repository\MetierChatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Carbon\Carbon;

#[ORM\Entity(repositoryClass: MetierChatRepository::class)]
class MetierChat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'metierChats')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sender = null;

    #[ORM\ManyToOne(inversedBy: 'receiverChats')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $receiver = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column]
    private ?bool $seen = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): static
    {
        $this->receiver = $receiver;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function getTime()
    {
        return Carbon::parse($this->getDate())->diffForHumans();
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function isSeen(): ?bool
    {
        return $this->seen;
    }

    public function setSeen(bool $seen): static
    {
        $this->seen = $seen;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }
}
