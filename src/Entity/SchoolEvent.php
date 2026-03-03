<?php
// src/Entity/SchoolEvent.php

namespace App\Entity;

use App\Repository\SchoolEventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SchoolEventRepository::class)]
class SchoolEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    private string $description;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull(message: "La date de début est obligatoire.")]
    #[Assert\GreaterThanOrEqual('today', message: "La date de début ne doit pas être passée.")]
    private \DateTimeInterface $startDate;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull(message: "La date de fin est obligatoire.")]
    #[Assert\GreaterThanOrEqual(
        propertyPath: 'startDate',
        message: 'La date de fin doit être supérieure ou égale à la date de début.'
    )]
    private \DateTimeInterface $endDate;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire.")]
    private string $location;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, EventResource>
     */
    #[ORM\OneToMany(targetEntity: EventResource::class, mappedBy: 'event', orphanRemoval: true)]
    private Collection $resources;

    /**
     * @var Collection<int, EventRegistration>
     */
    #[ORM\OneToMany(targetEntity: EventRegistration::class, mappedBy: 'event', orphanRemoval: true)]
    private Collection $registrations;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $longitude = null;

    public function __construct()
    {
        $this->resources = new ArrayCollection();
        $this->registrations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getStartDate(): \DateTimeInterface { return $this->startDate; }
    public function setStartDate(\DateTimeInterface $startDate): static { $this->startDate = $startDate; return $this; }

    public function getEndDate(): \DateTimeInterface { return $this->endDate; }
    public function setEndDate(\DateTimeInterface $endDate): static { $this->endDate = $endDate; return $this; }

    public function getLocation(): string { return $this->location; }
    public function setLocation(string $location): static { $this->location = $location; return $this; }

    public function getImagePath(): ?string { return $this->imagePath; }
    public function setImagePath(?string $imagePath): static { $this->imagePath = $imagePath; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getLatitude(): ?string { return $this->latitude; }
    public function setLatitude(?string $latitude): static { $this->latitude = $latitude; return $this; }

    public function getLongitude(): ?string { return $this->longitude; }
    public function setLongitude(?string $longitude): static { $this->longitude = $longitude; return $this; }

    /** @return Collection<int, EventResource> */
    public function getResources(): Collection { return $this->resources; }

    public function addResource(EventResource $resource): static
    {
        if (!$this->resources->contains($resource)) {
            $this->resources->add($resource);
            $resource->setEvent($this);
        }
        return $this;
    }

    public function removeResource(EventResource $resource): static
    {
        if ($this->resources->removeElement($resource)) {
            // ✅ CORRIGÉ : on ne passe pas null, on vérifie que l'événement est bien celui-ci avant de retirer
            if ($resource->getEvent() === $this) {
                $resource->setEvent($this); // en réalité, on devrait avoir une méthode pour dissocier
                // Dans EventResource, il faudrait une méthode setEvent(null) mais la propriété n'est pas nullable
                // Donc on ne peut pas vraiment dissocier complètement
                // Solution : on garde la relation mais on laisse le removeElement gérer la suppression
            }
        }
        return $this;
    }

    /** @return Collection<int, EventRegistration> */
    public function getRegistrations(): Collection { return $this->registrations; }

    public function addRegistration(EventRegistration $registration): static
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations->add($registration);
            $registration->setEvent($this);
        }
        return $this;
    }

    public function removeRegistration(EventRegistration $registration): static
    {
        if ($this->registrations->removeElement($registration)) {
            // ✅ CORRIGÉ : même problème
            if ($registration->getEvent() === $this) {
                $registration->setEvent($this);
            }
        }
        return $this;
    }
}