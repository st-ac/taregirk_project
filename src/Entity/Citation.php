<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "citation")]
#[ORM\HasLifecycleCallbacks]
class Citation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(type: "text")]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(targetEntity: Archives::class)]
    #[ORM\JoinColumn(name: "archivesid_FK", nullable: true)]
    private ?Archives $archive = null;

    // Date de création
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): self
    {
        $this->author = $author;
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

    public function getArchive(): ?Archives
    {
        return $this->archive;
    }

    public function setArchive(?Archives $archive): self
    {
        $this->archive = $archive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function toArray(): array
{
    return [
        'id'          => $this->getId(),
        'description' => $this->getDescription(),
        'author'      => $this->getAuthor(),
        'image'       => $this->getImage(), // ex: /uploads/images/citation_xxx.jpg
        'archive'     => $this->getArchive() ? [
            'id'    => $this->getArchive()->getId(),
            'title' => $this->getArchive()->getTitle(),
        ] : null,
    ];
}
}