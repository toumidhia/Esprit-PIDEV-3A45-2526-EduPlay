<?php
// src/Entity/Library.php

namespace App\Entity;

use App\Repository\LibraryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LibraryRepository::class)]
class Library
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le nom de la bibliothèque est requis')]
    #[Assert\Length(
        min: 3,
        max: 20,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[A-Za-zÀ-ÿ0-9\s\-\']+$/u',
        message: 'Caractères spéciaux non autorisés (sauf tiret et apostrophe)'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères')]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'L\'âge minimum est requis')]
    #[Assert\Range(min: 3, minMessage: 'L\'âge minimum doit être d\'au moins {{ limit }} ans')]
    private ?int $minAge = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'L\'âge maximum est requis')]
    #[Assert\Range(max: 12, maxMessage: 'L\'âge maximum ne peut pas dépasser {{ limit }} ans')]
    private ?int $maxAge = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le niveau de difficulté est requis')]
    #[Assert\Choice(
        choices: ['Débutant', 'Intermédiaire', 'Avancé', 'Expert'],
        message: 'Veuillez sélectionner un niveau valide'
    )]
    private ?string $level = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le thème est requis')]
    #[Assert\Length(
        min: 2,
        max: 20,
        minMessage: 'Le thème doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le thème ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $theme = null;

    #[ORM\OneToMany(targetEntity: Resource::class, mappedBy: 'libraryId')]
    private Collection $resources;

    public function __construct()
    {
        $this->resources = new ArrayCollection();
    }

    // Getters et setters
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;
        return $this;
    }

    public function getMinAge(): ?int
    {
        return $this->minAge;
    }

    public function setMinAge(int $minAge): static
    {
        $this->minAge = $minAge;
        return $this;
    }

    public function getMaxAge(): ?int
    {
        return $this->maxAge;
    }

    public function setMaxAge(int $maxAge): static
    {
        $this->maxAge = $maxAge;
        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * @return Collection<int, Resource>
     */
    public function getResources(): Collection
    {
        return $this->resources;
    }

    public function addResource(Resource $resource): static
    {
        if (!$this->resources->contains($resource)) {
            $this->resources->add($resource);
            $resource->setLibraryId($this);
        }

        return $this;
    }

    public function removeResource(Resource $resource): static
    {
        if ($this->resources->removeElement($resource)) {
            // set the owning side to null (unless already changed)
            if ($resource->getLibraryId() === $this) {
                $resource->setLibraryId(null);
            }
        }

        return $this;
    }

    // Méthode utilitaire pour obtenir le nombre de ressources
    public function getResourceCount(): int
    {
        return $this->resources->count();
    }

    // Méthode de validation personnalisée (pour vérifier minAge < maxAge)
    #[Assert\Callback]
    public function validateAges(mixed $context): void
    {
        if ($this->minAge !== null && $this->maxAge !== null && $this->minAge > $this->maxAge) {
            $context->buildViolation("L'âge maximum doit être supérieur ou égal à l'âge minimum")
                    ->atPath('maxAge')
                    ->addViolation();
        }
    }

    // Méthode toString pour l'affichage
    public function __toString(): string
    {
        return $this->name ?? '';
    }
}