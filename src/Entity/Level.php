<?php

namespace App\Entity;

use App\Repository\LevelRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: LevelRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Level
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255 , nullable: true)]
    #[Assert\NotBlank(message: 'Level name is required.')]
    #[Assert\Regex(
        pattern: '/^[\p{L}\s]+$/u',
        message: 'Name must contain letters only.'
    )]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Minimum 3 characters required.'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[\p{L}\s]+$/u',
        message: 'Description must contain only letters and spaces.'
    )]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: 'Minimum 10 characters required.'
    )]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotNull(message: 'Difficulty is required.')]
    #[Assert\Range(
        min: 1,
        max: 5,
        notInRangeMessage: 'Difficulty must be between {{ min }} and {{ max }}.'
    )]
    private ?int $difficulty = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotNull(message: 'Minimum age is required.')]
    #[Assert\Range(
        min: 1,
        max: 18,
        notInRangeMessage: 'Minimum age must be between {{ min }} and {{ max }}.'
    )]
    private ?int $minAge = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotNull(message: 'Maximum age is required.')]
    #[Assert\Range(
        min: 3,
        max: 16,
        notInRangeMessage: 'Maximum age must be between {{ min }} and {{ max }}.'
    )]
    private ?int $maxAge = null;

    #[ORM\Column(length: 255,nullable: true)]
    #[Assert\NotBlank(message: 'Educational goal is required.')]
    #[Assert\Regex(
        pattern: '/^[\p{L}\s]+$/u',
        message: 'Educational goal must contain letters only.'
    )]
    #[Assert\Length(
        min: 4,
        max: 255,
        minMessage: 'Minimum 4 characters required.'
    )]
    private ?string $pedagGoal = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // ✅ Règle métier au niveau Entity : maxAge > minAge
    #[Assert\Callback]
    public function validateAgeRange(ExecutionContextInterface $context): void
    {
        if ($this->minAge !== null && $this->maxAge !== null && $this->maxAge <= $this->minAge) {
            $context->buildViolation('Maximum age must be greater than minimum age.')
                ->atPath('maxAge') // ou 'minAge' ou rien pour erreur globale
                ->addViolation();
        }
    }

    // ✅ Dates auto
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // getters/setters...

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): static { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getDifficulty(): ?int { return $this->difficulty; }
    public function setDifficulty(?int $difficulty): static { $this->difficulty = $difficulty; return $this; }

    public function getMinAge(): ?int { return $this->minAge; }
    public function setMinAge(?int $minAge): static { $this->minAge = $minAge; return $this; }

    public function getMaxAge(): ?int { return $this->maxAge; }
    public function setMaxAge(?int $maxAge): static { $this->maxAge = $maxAge; return $this; }

    public function getPedagGoal(): ?string { return $this->pedagGoal; }
    public function setPedagGoal(?string $pedagGoal): static { $this->pedagGoal = $pedagGoal; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
}
