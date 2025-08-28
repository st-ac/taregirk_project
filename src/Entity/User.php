<?php

namespace App\Entity;

use App\Entity\Archive;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $username = null;

    #[ORM\Column(type: 'string', unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private ?string $password = null;

    #[ORM\OneToMany(mappedBy: "user", targetEntity: Archives::class)]
    private Collection $archives;

    public function __construct()
{
    $this->archives = new ArrayCollection();
}

public function getId(): ?int
{
    return $this->id;
}

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }



    public function eraseCredentials(): void
    {
    }

    public function getArchives(): Collection
    {
        return $this->archives;
    }

    public function addArchives(Archives $archive): self
    {
        if (!$this->archives->contains($archive)) {
            $this->archives->add($archive);
            $archive->setUser($this);
        }
        return $this;
    }

    public function removeArchives(Archives $archive): self
    {
        if ($this->archives->removeElement($archive)) {
            if ($archive->getUser() === $this) {
                $archive->setUser(null);
            }
        }
        return $this;
    }
}
