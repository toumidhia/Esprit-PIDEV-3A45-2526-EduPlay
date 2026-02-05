<?php

namespace App\Entity;

use App\Repository\SeanceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SeanceRepository::class)]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'The start time is required.')]
    #[Assert\Type(type: '\DateTime', message: 'Invalid start time format.')]
    private ?\DateTime $startTime = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'The end time is required.')]
    #[Assert\Type(type: '\DateTime', message: 'Invalid end time format.')]
    #[Assert\GreaterThan(
        propertyPath: 'startTime',
        message: 'The end time must be after the start time.'
    )]
    private ?\DateTime $endTime = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please select a course for this session.')]
    private ?Course $courseId = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCourseId(): ?Course
    {
        return $this->courseId;
    }

    public function setCourseId(?Course $courseId): static
    {
        $this->courseId = $courseId;

        return $this;
    }
}
