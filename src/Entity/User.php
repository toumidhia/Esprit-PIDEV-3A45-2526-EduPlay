<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
#[UniqueEntity(fields: ['username'], message: 'Cet identifiant est déjà utilisé')]
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
    private ?\DateTime $birthDate = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $email = null;

    // Username pour les enfants (identifiant de connexion)
    #[ORM\Column(length: 100, unique: true, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'Le mot de passe est obligatoire.', groups: ['Default', 'edit', 'login'])]
    private ?string $password = null;

    // Type: 'parent', 'enfant', 'admin', 'enseignant'
    #[ORM\Column(length: 255)]
    private ?string $type = null;

    // Renommé de 'role' à 'roles' pour respecter l'interface UserInterface
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    // Champs supplémentaires selon le type d'utilisateur
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null; // Pour parent et enseignant

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null; // Pour parent

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $specialite = null; // Pour enseignant

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $niveau = null; // Pour enfant (CP, CE1, etc.)

    // Relation parent-enfant (un parent a plusieurs enfants)
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfants')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['persist', 'remove'])]
    private Collection $enfants;

    // VOS RELATIONS EXISTANTES
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
        $this->createdAt = new \DateTime();
        $this->active = true;
        $this->enfants = new ArrayCollection();
        $this->courses = new ArrayCollection();
        $this->eventRegistrations = new ArrayCollection();
        $this->commandes = new ArrayCollection();
    }

    // === GETTERS ET SETTERS ===

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

    public function getBirthDate(): ?\DateTime
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTime $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;
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
        
        // Définir le rôle automatiquement selon le type
        $roleMap = [
            'parent' => 'ROLE_PARENT',
            'enfant' => 'ROLE_ENFANT',
            'admin' => 'ROLE_ADMIN',
            'enseignant' => 'ROLE_ENSEIGNANT',
        ];
        
        if (isset($roleMap[$type])) {
            $this->roles = [$roleMap[$type]];
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

    // ANCIEN: getRole() - GARDÉ pour compatibilité si nécessaire
    public function getRole(): array
    {
        return $this->roles;
    }

    // ANCIEN: setRole() - GARDÉ pour compatibilité si nécessaire
    public function setRole(array $role): static
    {
        $this->roles = $role;
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

    // === RELATION PARENT-ENFANT ===

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
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

    // === VOS RELATIONS EXISTANTES ===

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
        // Les enfants utilisent username, les autres email
        return (string) ($this->username ?? $this->email);
    }

    public function eraseCredentials(): void
    {
        // Nettoyer les données sensibles temporaires si nécessaire
    }

    // === MÉTHODES UTILITAIRES ===

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getAge(): ?int
    {
        if (!$this->birthDate) {
            return null;
        }
        return (new \DateTime())->diff($this->birthDate)->y;
    }

    public function isParent(): bool
    {
        return $this->type === 'parent';
    }

    public function isEnfant(): bool
    {
        return $this->type === 'enfant';
    }

    public function isAdmin(): bool
    {
        return $this->type === 'admin';
    }

    public function isEnseignant(): bool
    {
        return $this->type === 'enseignant';
    }
}