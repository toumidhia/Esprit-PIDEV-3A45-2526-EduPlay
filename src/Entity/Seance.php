<?php

namespace App\Entity;

use App\Repository\SeanceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SeanceRepository::class)]
#[ORM\Table(name: 'seance')]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $title = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\Type(type: '\DateTimeInterface', message: 'Veuillez entrer une date valide')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: 'start_time', type: 'datetime')]
    #[Assert\NotBlank(message: 'L\'heure de début est obligatoire')]
    #[Assert\Type(type: '\DateTime', message: 'Veuillez entrer une heure valide')]
    private ?\DateTime $startTime = null;

    #[ORM\Column(name: 'end_time', type: 'datetime')]
    #[Assert\NotBlank(message: 'L\'heure de fin est obligatoire')]
    #[Assert\Type(type: '\DateTime', message: 'Veuillez entrer une heure valide')]
    #[Assert\GreaterThan(
        propertyPath: 'startTime',
        message: 'L\'heure de fin doit être après l\'heure de début'
    )]
    private ?\DateTime $endTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le lieu ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $location = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ['scheduled', 'ongoing', 'completed', 'cancelled'],
        message: 'Statut invalide. Choisissez parmi: scheduled, ongoing, completed, cancelled'
    )]
    private string $status = 'scheduled';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'seances')]
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'Veuillez sélectionner un cours pour cette séance')]
    private ?Course $course = null;

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->startTime = new \DateTime();
        $this->endTime = new \DateTime('+1 hour');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTime $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTime $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;
        return $this;
    }

    // For backward compatibility
    public function getCourseId(): ?Course
    {
        return $this->course;
    }

    public function setCourseId(?Course $course): static
    {
        $this->course = $course;
        return $this;
    }

    // Helper methods
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'scheduled' => 'Planifiée',
            'ongoing' => 'En cours',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
            default => $this->status
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'scheduled' => 'primary',
            'ongoing' => 'success',
            'completed' => 'secondary',
            'cancelled' => 'danger',
            default => 'dark'
        };
    }

    public function getFormattedDateTime(): string
    {
        if (!$this->date || !$this->startTime || !$this->endTime) {
            return '';
        }
        return $this->date->format('d/m/Y') . ' ' .
            $this->startTime->format('H:i') . ' - ' .
            $this->endTime->format('H:i');
    }

    public function __toString(): string
    {
        if ($this->course && $this->date) {
            return $this->course->getTitle() . ' - ' . $this->date->format('d/m/Y');
        }
        return $this->title ?? 'Séance #' . $this->id;
    }
}