<?php
// src/Entity/EventRegistration.php

namespace App\Entity;

use App\Repository\EventRegistrationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRegistrationRepository::class)]
class EventRegistration
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $status = self::STATUS_PENDING; // plus de ?

    #[ORM\Column]
    private \DateTimeImmutable $registeredAt; // plus de ?

    #[ORM\Column(length: 120)]
    private string $childFullName; // plus de ?

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $parentPhone = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $childClassLevel = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $medicalNotes = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $emergencyContactName = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $emergencyContactPhone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false)]
    private SchoolEvent $event; // plus de ?

    #[ORM\ManyToOne(inversedBy: 'eventRegistrations')]
    #[ORM\JoinColumn(nullable: false)]
    private User $parent; // plus de ?

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ticketQrCode = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $scannedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qrCodePath = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $reminderSent = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $reminderSentAt = null;

    public function __construct()
    {
        $this->registeredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static 
    { 
        $this->status = $status; 
        return $this; 
    }

    public function getRegisteredAt(): \DateTimeImmutable { return $this->registeredAt; }
    public function setRegisteredAt(\DateTimeImmutable $registeredAt): static 
    { 
        $this->registeredAt = $registeredAt; 
        return $this; 
    }

    public function getChildFullName(): string { return $this->childFullName; }
    public function setChildFullName(string $childFullName): static 
    { 
        $this->childFullName = $childFullName; 
        return $this; 
    }

    public function getParentPhone(): ?string { return $this->parentPhone; }
    public function setParentPhone(?string $parentPhone): static 
    { 
        $this->parentPhone = $parentPhone; 
        return $this; 
    }

    public function getChildClassLevel(): ?string { return $this->childClassLevel; }
    public function setChildClassLevel(?string $childClassLevel): static 
    { 
        $this->childClassLevel = $childClassLevel; 
        return $this; 
    }

    public function getMedicalNotes(): ?string { return $this->medicalNotes; }
    public function setMedicalNotes(?string $medicalNotes): static 
    { 
        $this->medicalNotes = $medicalNotes; 
        return $this; 
    }

    public function getEmergencyContactName(): ?string { return $this->emergencyContactName; }
    public function setEmergencyContactName(?string $emergencyContactName): static 
    { 
        $this->emergencyContactName = $emergencyContactName; 
        return $this; 
    }

    public function getEmergencyContactPhone(): ?string { return $this->emergencyContactPhone; }
    public function setEmergencyContactPhone(?string $emergencyContactPhone): static 
    { 
        $this->emergencyContactPhone = $emergencyContactPhone; 
        return $this; 
    }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static 
    { 
        $this->notes = $notes; 
        return $this; 
    }

    public function getEvent(): SchoolEvent { return $this->event; }
    public function setEvent(SchoolEvent $event): static 
    { 
        $this->event = $event; 
        return $this; 
    }

    public function getParent(): User { return $this->parent; }
    public function setParent(User $parent): static 
    { 
        $this->parent = $parent; 
        return $this; 
    }

    public function getTicketQrCode(): ?string { return $this->ticketQrCode; }
    public function setTicketQrCode(?string $ticketQrCode): static 
    { 
        $this->ticketQrCode = $ticketQrCode; 
        return $this; 
    }

    public function getScannedAt(): ?\DateTimeInterface { return $this->scannedAt; }
    public function setScannedAt(?\DateTimeInterface $scannedAt): static 
    { 
        $this->scannedAt = $scannedAt; 
        return $this; 
    }

    public function isScanned(): bool
    {
        return $this->scannedAt !== null;
    }

    public function getQrCodePath(): ?string { return $this->qrCodePath; }
    public function setQrCodePath(?string $qrCodePath): static 
    { 
        $this->qrCodePath = $qrCodePath; 
        return $this; 
    }

    public function getReminderSent(): ?bool { return $this->reminderSent; }
    public function setReminderSent(?bool $reminderSent): static 
    { 
        $this->reminderSent = $reminderSent; 
        return $this; 
    }

    public function getReminderSentAt(): ?\DateTimeInterface { return $this->reminderSentAt; }
    public function setReminderSentAt(?\DateTimeInterface $reminderSentAt): static 
    { 
        $this->reminderSentAt = $reminderSentAt; 
        return $this; 
    }
}