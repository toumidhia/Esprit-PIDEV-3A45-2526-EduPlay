<?php

namespace App\Entity;

use App\Repository\EventRegistrationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRegistrationRepository::class)]
class EventRegistration
{
    public const STATUS_PENDING = 'PENDING';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private ?\DateTimeImmutable $registeredAt = null;

    #[ORM\Column(length: 120)]
    private ?string $childFullName = null;

    // ✅ Nouveaux champs
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $parentPhone = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $childClassLevel = null; // ex: "3A", "CE2", ...

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $medicalNotes = null; // allergies, asthme...

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $emergencyContactName = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $emergencyContactPhone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SchoolEvent $event = null;

    #[ORM\ManyToOne(inversedBy: 'eventRegistrations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $parent = null;

    public function getId(): ?int { return $this->id; }

    public function getStatus(): ?string { return $this->status; }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getRegisteredAt(): ?\DateTimeImmutable { return $this->registeredAt; }

    public function setRegisteredAt(\DateTimeImmutable $registeredAt): static
    {
        $this->registeredAt = $registeredAt;
        return $this;
    }

    public function getChildFullName(): ?string { return $this->childFullName; }

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

    public function getEvent(): ?SchoolEvent { return $this->event; }

    public function setEvent(?SchoolEvent $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getParent(): ?User { return $this->parent; }

    public function setParent(?User $parent): static
    {
        $this->parent = $parent;
        return $this;
    }
}
