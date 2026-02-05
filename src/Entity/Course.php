<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The course title is required.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'The course title must be at least {{ limit }} characters long.',
        maxMessage: 'The course title cannot be longer than {{ limit }} characters.'
    )]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The duration of training is required.')]
    #[Assert\Regex(
        pattern: '/^[0-9]+\s*(hour|hours|day|days|week|weeks|month|months)$/i',
        message: 'Please enter a valid duration (e.g., "5 hours", "2 weeks", "1 month").'
    )]
    private ?string $durationTraining = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The course description is required.')]
    #[Assert\Length(
        min: 10,
        max: 255,
        minMessage: 'The description must be at least {{ limit }} characters long.',
        maxMessage: 'The description cannot be longer than {{ limit }} characters.'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The course level is required.')]
    #[Assert\Choice(
        choices: ['Beginner', 'Intermediate', 'Advanced'],
        message: 'Please choose a valid level: Beginner, Intermediate, or Advanced.'
    )]
    private ?string $level = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex(
        pattern: '/^.*\.(pdf|PDF)$/',
        message: 'Please upload a valid PDF file.'
    )]
    private ?string $pdfFile = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(
        choices: ['pending', 'accepted', 'rejected'],
        message: 'Invalid status. Must be pending, accepted, or rejected.'
    )]
    private ?string $status = 'pending';

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

    public function getstatus(): ?string
    {
        return $this->status;
    }

    public function setstatus(string $status): static
    {
        $this->status = $status;

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
