<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Archive;

#[ORM\Entity]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Archives::class)]
    private Collection $archives;

    public function __construct()
    {
        $this->archives = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getArchives(): Collection
    {
        return $this->archives;
    }

    public function addArchive(Archive $archives): self
    {
        if (!$this->archives->contains($archives)) {
            $this->archives->add($archives);
            $archives->setCategory($this);
        }
        return $this;
    }

    public function removeArchive(Archive $archive): self
    {
        if ($this->archives->removeElement($archive)) {
            if ($archive->getCategory() === $this) {
                $archive->setCategory(null);
            }
        }
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'image' => $this->getImage() 
            ? 'http://127.0.0.1:8000/uploads/category/' 
            . $this->getImage() 
            : null,            
            'createdAt' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}