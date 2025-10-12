<?php

namespace App\Entity;

use App\Repository\KaabaScholarshipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: KaabaScholarshipRepository::class)]
class KaabaScholarship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $closing_date = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;

    /**
     * @var Collection<int, KaabaApplication>
     */
    #[ORM\OneToMany(targetEntity: KaabaApplication::class, mappedBy: 'scholarship', orphanRemoval: true)]
    private Collection $kaabaApplications;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $so_title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $so_content = null;


 #[ORM\Column(length: 100, nullable: true)]
    private ?string $type = null;


  /**
     * @var Collection<int, KaabaInstitute>
     */
    #[ORM\OneToMany(targetEntity: KaabaInstitute::class, mappedBy: 'scholarship')]
    private Collection $institutes;

    public function __construct()
    {
         $this->uuid = Uuid::v4();
         $this->kaabaApplications = new ArrayCollection();
        $this->institutes = new ArrayCollection();

    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

         public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

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

    public function getClosingDate(): ?\DateTimeInterface
    {
        return $this->closing_date;
    }

    public function setClosingDate(\DateTimeInterface $closing_date): static
    {
        $this->closing_date = $closing_date;

        return $this;
    }

    /**
     * @return Collection<int, KaabaApplication>
     */
    public function getKaabaApplications(): Collection
    {
        return $this->kaabaApplications;
    }

    public function addKaabaApplication(KaabaApplication $kaabaApplication): static
    {
        if (!$this->kaabaApplications->contains($kaabaApplication)) {
            $this->kaabaApplications->add($kaabaApplication);
            $kaabaApplication->setScholarship($this);
        }

        return $this;
    }

    public function removeKaabaApplication(KaabaApplication $kaabaApplication): static
    {
        if ($this->kaabaApplications->removeElement($kaabaApplication)) {
            // set the owning side to null (unless already changed)
            if ($kaabaApplication->getScholarship() === $this) {
                $kaabaApplication->setScholarship(null);
            }
        }

        return $this;
    }

    public function getSoTitle(): ?string
    {
        return $this->so_title;
    }

    public function setSoTitle(?string $so_title): static
    {
        $this->so_title = $so_title;

        return $this;
    }

    public function getSoContent(): ?string
    {
        return $this->so_content;
    }

    public function setSoContent(?string $so_content): static
    {
        $this->so_content = $so_content;

        return $this;
    }


 public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    // Helper method to check if this is a Literacy & Numeracy scholarship
    public function isLiteracyNumeracyScholarship(): bool
    {
        return $this->type === 'Literacy & Numeracy Scholarship';
    }


  /**
     * @return Collection<int, KaabaInstitute>
     */
    public function getInstitutes(): Collection
    {
        return $this->institutes;
    }

    public function addInstitute(KaabaInstitute $institute): static
    {
        if (!$this->institutes->contains($institute)) {
            $this->institutes->add($institute);
            $institute->setScholarship($this);
        }

        return $this;
    }

    public function removeInstitute(KaabaInstitute $institute): static
    {
        if ($this->institutes->removeElement($institute)) {
            // set the owning side to null (unless already changed)
            if ($institute->getScholarship() === $this) {
                $institute->setScholarship(null);
            }
        }

        return $this;
    }
}
