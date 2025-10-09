<?php

namespace App\Entity;

use App\Repository\MetierBlockedUserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierBlockedUserRepository::class)]
class MetierBlockedUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'metierBlockedByUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $blocked_by = null;

    #[ORM\ManyToOne(inversedBy: 'metierBlockedUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $blocked_user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBlockedBy(): ?User
    {
        return $this->blocked_by;
    }

    public function setBlockedBy(?User $blocked_by): static
    {
        $this->blocked_by = $blocked_by;

        return $this;
    }

    public function getBlockedUser(): ?User
    {
        return $this->blocked_user;
    }

    public function setBlockedUser(?User $blocked_user): static
    {
        $this->blocked_user = $blocked_user;

        return $this;
    }
}
