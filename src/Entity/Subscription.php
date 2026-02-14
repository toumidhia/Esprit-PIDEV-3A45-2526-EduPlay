<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'parent_id', nullable: false)]
    private ?User $parent = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'kid_id', nullable: false)]
    private ?User $kid = null;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'course_id', nullable: false)]
    private ?Course $course = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $subscribedAt = null;

    #[ORM\Column]
    private ?bool $active = true;

    public function __construct()
    {
        $this->subscribedAt = new \DateTime();
    }

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?User
    {
        return $this->parent;
    }

    public function setParent(?User $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    public function getKid(): ?User
    {
        return $this->kid;
    }

    public function setKid(?User $kid): static
    {
        $this->kid = $kid;
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

    public function getSubscribedAt(): ?\DateTimeInterface
    {
        return $this->subscribedAt;
    }

    public function setSubscribedAt(\DateTimeInterface $subscribedAt): static
    {
        $this->subscribedAt = $subscribedAt;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    // Backward compatibility methods
    public function getParentId(): ?User
    {
        return $this->parent;
    }

    public function setParentId(?User $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    public function getKidId(): ?User
    {
        return $this->kid;
    }

    public function setKidId(?User $kid): static
    {
        $this->kid = $kid;
        return $this;
    }

    public function getCourseId(): ?Course
    {
        return $this->course;
    }

    public function setCourseId(?Course $course): static
    {
        $this->course = $course;
        return $this;
    }
}