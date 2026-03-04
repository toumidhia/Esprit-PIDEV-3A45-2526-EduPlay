<?php
// src/Entity/BookRequest.php

namespace App\Entity;

use App\Repository\BookRequestRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRequestRepository::class)]
class BookRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $bookTitle;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $enfant;

    #[ORM\Column]
    private bool $isNotified = false;

    #[ORM\Column]
    private bool $isAvailable = false;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $requestedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $notifiedAt = null;

    #[ORM\ManyToOne(targetEntity: Resource::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Resource $resource = null;

    public function __construct()
    {
        $this->requestedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getBookTitle(): string { return $this->bookTitle; }
    public function setBookTitle(string $t): static { $this->bookTitle = $t; return $this; }

    public function getEnfant(): User { return $this->enfant; }
    public function setEnfant(User $enfant): static { $this->enfant = $enfant; return $this; }

    public function isNotified(): bool { return $this->isNotified; }
    public function setIsNotified(bool $n): static { $this->isNotified = $n; return $this; }

    public function isAvailable(): bool { return $this->isAvailable; }
    public function setIsAvailable(bool $a): static { $this->isAvailable = $a; return $this; }

    public function getRequestedAt(): \DateTimeInterface { return $this->requestedAt; }

    public function getNotifiedAt(): ?\DateTimeInterface { return $this->notifiedAt; }
    public function setNotifiedAt(?\DateTimeInterface $d): static { $this->notifiedAt = $d; return $this; }

    public function getResource(): ?Resource { return $this->resource; }
    public function setResource(?Resource $r): static { $this->resource = $r; return $this; }

    
    /**
     * Mark this request as notified
     * This should be the only way to set the notification timestamp
     */
    public function markAsNotified(): static
    {
        $this->isNotified = true;
        $this->notifiedAt = new \DateTime();
        return $this;
    }
}