<?php

namespace App\Entity;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use App\Repository\MetierNotificationRepository;
#[ORM\Entity(repositoryClass: MetierNotificationRepository::class)]
#[HasLifecycleCallbacks]
class MetierNotification
{
    use Timestamps;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $is_read = null;

    #[ORM\Column(length: 255)]
    private ?string $action = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
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

    public function isRead(): ?bool
    {
        return $this->is_read;
    }

    public function setRead(bool $is_read): static
    {
        $this->is_read = $is_read;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }
    public function getPostedAt()
    {
        // dd(Carbon::parse($this->getCreatedAt())->diffForHumans());
        return Carbon::parse($this->getCreatedAt())->diffForHumans();
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }
}
