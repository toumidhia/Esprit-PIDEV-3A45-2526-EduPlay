<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $durationTraining = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $level = null;

    #[ORM\Column(length: 255)]
    private ?string $pdfFile = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'courses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $teacherId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDurationTraining(): ?string
    {
        return $this->durationTraining;
    }

    public function setDurationTraining(string $durationTraining): static
    {
        $this->durationTraining = $durationTraining;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

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

    public function getPdfFile(): ?string
    {
        return $this->pdfFile;
    }

    public function setPdfFile(string $pdfFile): static
    {
        $this->pdfFile = $pdfFile;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getTeacherId(): ?User
    {
        return $this->teacherId;
    }

    public function setTeacherId(?User $teacherId): static
    {
        $this->teacherId = $teacherId;

        return $this;
    }
}
