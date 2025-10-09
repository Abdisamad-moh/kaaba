<?php

namespace App\Entity;

use App\Repository\MetierAdsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetierAdsRepository::class)]
class MetierAds
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $client = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'integer')]
    private ?int $status = null;

    #[ORM\Column(length: 255)]
    private ?string $link = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deadline = null;

    #[ORM\ManyToOne(inversedBy: 'metierAds')]
    private ?User $requested_by = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact_email = null;

    /**
     * @var Collection<int, MetierAdPage>
     */
    #[ORM\ManyToMany(targetEntity: MetierAdPage::class, inversedBy: 'metierAds')]
    private Collection $pages;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?string
    {
        return $this->client;
    }

    public function setClient(string $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getDeadline(): ?\DateTimeInterface
    {
        return $this->deadline;
    }

    public function setDeadline(?\DateTimeInterface $deadline): static
    {
        $this->deadline = $deadline;

        return $this;
    }

    public function getRequestedBy(): ?User
    {
        return $this->requested_by;
    }

    public function setRequestedBy(?User $requested_by): static
    {
        $this->requested_by = $requested_by;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contact_email;
    }

    public function setContactEmail(?string $contact_email): static
    {
        $this->contact_email = $contact_email;

        return $this;
    }

    /**
     * @return Collection<int, MetierAdPage>
     */
    public function getPages(): Collection
    {
        return $this->pages;
    }

    public function addPage(MetierAdPage $page): static
    {
        if (!$this->pages->contains($page)) {
            $this->pages->add($page);
        }

        return $this;
    }

    public function removePage(MetierAdPage $page): static
    {
        $this->pages->removeElement($page);

        return $this;
    }
}
