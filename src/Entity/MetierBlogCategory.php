<?php

namespace App\Entity;

use App\Repository\MetierBlogCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
#[HasLifecycleCallbacks]

#[ORM\Entity(repositoryClass: MetierBlogCategoryRepository::class)]
class MetierBlogCategory
{
    use Timestamps;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, MetierBlog>
     */
    #[ORM\OneToMany(targetEntity: MetierBlog::class, mappedBy: 'category')]
    private Collection $metierBlogs;

    public function __construct()
    {
        $this->metierBlogs = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, MetierBlog>
     */
    public function getMetierBlogs(): Collection
    {
        return $this->metierBlogs;
    }

    public function addMetierBlog(MetierBlog $metierBlog): static
    {
        if (!$this->metierBlogs->contains($metierBlog)) {
            $this->metierBlogs->add($metierBlog);
            $metierBlog->setCategory($this);
        }

        return $this;
    }

    public function removeMetierBlog(MetierBlog $metierBlog): static
    {
        if ($this->metierBlogs->removeElement($metierBlog)) {
            // set the owning side to null (unless already changed)
            if ($metierBlog->getCategory() === $this) {
                $metierBlog->setCategory(null);
            }
        }

        return $this;
    }
}
