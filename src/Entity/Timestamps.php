<?php

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;

trait Timestamps
{
    #[ORM\Column(nullable: true)]
    private ?\DateTime $createdAt = null;

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setTimeStamps()
    {
        if($this->getCreatedAt() == null){
            $this->setCreatedAt(new \DateTime());
        }
    }
}