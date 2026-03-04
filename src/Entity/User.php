<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

// 💡 Best Practice: implement UserInterface for Symfony security integration
// 🔒 Security: implement PasswordAuthenticatedUserInterface for password hashing
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]  // 💡 Best Practice: explicit table name
#[ORM\UniqueConstraint(name: 'UNIQ_EMAIL', fields: ['email'])]  // 🔒 Security: enforce unique email at DB level
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // 🔒 Security: unique email is the identifier for login
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    // 🔒 Security: store roles as JSON array e.g. ["ROLE_USER", "ROLE_ADMIN"]
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    // 🔒 Security: NEVER store plain text password — always hashed
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;  // 💡 default true

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    // 💡 Best Practice: set createdAt automatically in constructor
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];  // 🔒 every user gets ROLE_USER by default
    }

    // --- Getters & Setters ---

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static
    {
        // 🔒 Security: always normalize email to lowercase
        $this->email = strtolower(trim($email));
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER'; // 🔒 guarantee every user always has ROLE_USER
        return array_unique($roles);
    }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $lastName): static { $this->lastName = $lastName; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    // 💡 Helper: get full name
    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    // 🔒 Required by UserInterface — return the unique identifier (email)
    public function getUserIdentifier(): string { return (string) $this->email; }

    // 🔒 Required by UserInterface — clear temp sensitive data (not needed here but must exist)
    public function eraseCredentials(): void {}
    
    // Add this property inside the class
    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?AlumniProfile $profile = null;

    // Add this getter
    public function getProfile(): ?AlumniProfile
    {
        return $this->profile;
    }

}