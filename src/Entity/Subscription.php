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

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $parentId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $kidId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $courseId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $subscribedAt = null;

    #[ORM\Column]
    private ?bool $active = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentId(): ?User
    {
        return $this->parentId;
    }

    public function setParentId(?User $parentId): static
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getKidId(): ?User
    {
        return $this->kidId;
    }

    public function setKidId(?User $kidId): static
    {
        $this->kidId = $kidId;

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
}
