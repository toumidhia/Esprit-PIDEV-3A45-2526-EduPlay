<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'Le mot de passe est obligatoire.', groups: ['Default', 'edit', 'login'])]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null; // admin, teacher, parent, kid

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private ?bool $active = true;

    /**
     * @var Collection<int, Course>
     */
    #[ORM\OneToMany(targetEntity: Course::class, mappedBy: 'teacherId', orphanRemoval: true)]
    private Collection $courses;

    /**
     * @var Collection<int, EventRegistration>
     */
    #[ORM\OneToMany(targetEntity: EventRegistration::class, mappedBy: 'parent')]
    private Collection $eventRegistrations;

    /**
     * @var Collection<int, Commande>
     */
    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'user')]
    private Collection $commandes;

    public function __construct()
    {
        $this->courses = new ArrayCollection();
        $this->eventRegistrations = new ArrayCollection();
        $this->commandes = new ArrayCollection();
    }

    // Parent methods
    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function addEnfant(self $enfant): static
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants->add($enfant);
            $enfant->setParent($this);
        }
        return $this;
    }

    public function removeEnfant(self $enfant): static
    {
        if ($this->enfants->removeElement($enfant)) {
            if ($enfant->getParent() === $this) {
                $enfant->setParent(null);
            }
        }
        return $this;
    }

    // Basic getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        // Automatically assign roles based on type
        $this->roles = ['ROLE_USER'];
        switch ($type) {
            case 'admin':
                $this->roles[] = 'ROLE_ADMIN';
                break;
            case 'teacher':
                $this->roles[] = 'ROLE_TEACHER';
                break;
            case 'parent':
                $this->roles[] = 'ROLE_PARENT';
                break;
            case 'kid':
                $this->roles[] = 'ROLE_KID';
                // Generate username for kids if not set
                if (!$this->username && $this->firstName && $this->lastName) {
                    $baseUsername = strtolower($this->firstName . '.' . $this->lastName);
                    $this->username = $baseUsername . rand(100, 999);
                }
                break;
        }

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    // Additional properties getters and setters
    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(?string $specialite): static
    {
        $this->specialite = $specialite;
        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(?string $niveau): static
    {
        $this->niveau = $niveau;
        return $this;
    }

    // Course methods
    /**
     * @return Collection<int, Course>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function addCourse(Course $course): static
    {
        if (!$this->courses->contains($course)) {
            $this->courses->add($course);
            $course->setTeacherId($this);
        }
        return $this;
    }

    public function removeCourse(Course $course): static
    {
        if ($this->courses->removeElement($course)) {
            if ($course->getTeacherId() === $this) {
                $course->setTeacherId(null);
            }
        }
        return $this;
    }

    // EventRegistration methods
    /**
     * @return Collection<int, EventRegistration>
     */
    public function getEventRegistrations(): Collection
    {
        return $this->eventRegistrations;
    }

    public function addEventRegistration(EventRegistration $eventRegistration): static
    {
        if (!$this->eventRegistrations->contains($eventRegistration)) {
            $this->eventRegistrations->add($eventRegistration);
            $eventRegistration->setParent($this);
        }
        return $this;
    }

    public function removeEventRegistration(EventRegistration $eventRegistration): static
    {
        if ($this->eventRegistrations->removeElement($eventRegistration)) {
            if ($eventRegistration->getParent() === $this) {
                $eventRegistration->setParent(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    // === MÉTHODES POUR L'INTERFACE USERINTERFACE ===

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    // For Symfony < 5.3 compatibility
    public function getUsernameCompat(): string
    {
        return $this->getUserIdentifier();
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    // Helper methods
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles());
    }

    public function isTeacher(): bool
    {
        return in_array('ROLE_TEACHER', $this->getRoles());
    }

    public function isParent(): bool
    {
        return in_array('ROLE_PARENT', $this->getRoles());
    }

    public function isKid(): bool
    {
        return in_array('ROLE_KID', $this->getRoles()) || $this->type === 'kid';
    }

    // ADD THESE METHODS FOR FRENCH NAMING CONVENTION
    public function isEnfant(): bool
    {
        return $this->isKid();
    }

    public function getIsEnfant(): bool
    {
        return $this->isKid();
    }

    public function isisEnfant(): bool
    {
        return $this->isKid();
    }

    public function hasisEnfant(): bool
    {
        return $this->isKid();
    }

    // Age calculation
    public function getAge(): ?int
    {
        if (!$this->birthDate) {
            return null;
        }

        $now = new \DateTime();
        $interval = $this->birthDate->diff($now);
        return $interval->y;
    }
}