<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\KaabaCourseRepository;

#[ORM\Entity(repositoryClass: KaabaCourseRepository::class)]
class KaabaCourse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


#[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;


    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, KaabaApplication >
     */
    #[ORM\OneToMany(targetEntity: KaabaApplication ::class, mappedBy: 'course')]
    private Collection $kaabaApplications;

    #[ORM\ManyToOne(inversedBy: 'kaabaCourses')]
    private ?KaabaInstitute $institute = null;



     public function __construct()
    {
         $this->uuid = Uuid::v4();
         $this->kaabaApplications = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
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

        public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return Collection<int, KaabaApplication >
     */
    public function getkaabaApplications(): Collection
    {
        return $this->kaabaApplications;
    }

    public function addKaabaApplication (KaabaApplication  $KaabaApplication ): static
    {
        if (!$this->kaabaApplications->contains($KaabaApplication )) {
            $this->kaabaApplications->add($KaabaApplication );
            $KaabaApplication ->setCourse($this);
        }

        return $this;
    }

    public function removeKaabaApplication (KaabaApplication  $KaabaApplication ): static
    {
        if ($this->kaabaApplications->removeElement($KaabaApplication )) {
            // set the owning side to null (unless already changed)
            if ($KaabaApplication ->getCourse() === $this) {
                $KaabaApplication ->setCourse(null);
            }
        }

        return $this;
    }

    public function getInstitute(): ?KaabaInstitute
    {
        return $this->institute;
    }

    public function setInstitute(?KaabaInstitute $institute): static
    {
        $this->institute = $institute;

        return $this;
    }

    
}
