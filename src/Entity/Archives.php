<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Category;

#[ORM\Entity]
class Archives
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'json')]
    private array $images = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'pending';

    #[ORM\Column(type: 'string', length: 255)]
    private string $author;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "archives")]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: "archives")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getImages(): ?array
{
    return $this->images;
}

public function setImages(?array $images): self
{
    $this->images = $images;
    return $this;
}

public function addImages(string $images): self
{
    $this->images[] = $images;
    return $this;
}

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $allowed = ['pending', 'accepted', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException("Statut invalide : $status");
        }
        $this->status = $status;
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function toArray(): array
{
    return [
        'id'         => $this->getId(),
        'title'      => $this->getTitle(),
        'description'=> $this->getDescription(),
        'author'     => $this->getAuthor(),
        'status'     => $this->getStatus(),
        'createdAt'  => $this->getCreatedAt()?->format('Y-m-d H:i:s'),
        'images'     => $this->getImages() ?? [], 
        'category'   => $this->getCategory()?->getTitle(),
        'user'       => $this->getUser()?->getUsername(),
    ];
}
    
}