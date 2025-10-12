<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Repository\KaabaInstituteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: KaabaInstituteRepository::class)]
class KaabaInstitute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

     #[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;

    /**
     * @var Collection<int, KaabaApplication>
     */
    #[ORM\OneToMany(targetEntity: KaabaApplication::class, mappedBy: 'institute', orphanRemoval: true)]
    private Collection $kaabaApplications;

    /**
     * @var Collection<int, KaabaCourse>
     */
    #[ORM\OneToMany(targetEntity: KaabaCourse::class, mappedBy: 'institute')]
    private Collection $kaabaCourses;


 #[ORM\ManyToOne(inversedBy: 'institutes')]
    #[ORM\JoinColumn(nullable: true)] // Institute MUST belong to a scholarship
    private ?KaabaScholarship $scholarship = null;


    public function __construct()
    {
        $this->kaabaApplications = new ArrayCollection();
         $this->uuid = Uuid::v4();
         $this->kaabaCourses = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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
            $kaabaApplication->setInstitute($this);
        }

        return $this;
    }

    public function removeKaabaApplication(KaabaApplication $kaabaApplication): static
    {
        if ($this->kaabaApplications->removeElement($kaabaApplication)) {
            // set the owning side to null (unless already changed)
            if ($kaabaApplication->getInstitute() === $this) {
                $kaabaApplication->setInstitute(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, KaabaCourse>
     */
    public function getKaabaCourses(): Collection
    {
        return $this->kaabaCourses;
    }

    public function addKaabaCourse(KaabaCourse $kaabaCourse): static
    {
        if (!$this->kaabaCourses->contains($kaabaCourse)) {
            $this->kaabaCourses->add($kaabaCourse);
            $kaabaCourse->setInstitute($this);
        }

        return $this;
    }

    public function removeKaabaCourse(KaabaCourse $kaabaCourse): static
    {
        if ($this->kaabaCourses->removeElement($kaabaCourse)) {
            // set the owning side to null (unless already changed)
            if ($kaabaCourse->getInstitute() === $this) {
                $kaabaCourse->setInstitute(null);
            }
        }

        return $this;
    }


  public function getScholarship(): ?KaabaScholarship
    {
        return $this->scholarship;
    }

    public function setScholarship(?KaabaScholarship $scholarship): static
    {
        $this->scholarship = $scholarship;

        return $this;
    }
}
