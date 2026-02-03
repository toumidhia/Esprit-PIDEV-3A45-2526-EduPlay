<?php

namespace App\Entity;

use App\Repository\ResourceAccessRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResourceAccessRepository::class)]
class ResourceAccess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Resource $resourceId = null;

    #[ORM\ManyToOne]
    private ?User $childId = null;

    #[ORM\Column(nullable: true)]
    private ?int $openCount = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isCompleted = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResourceId(): ?Resource
    {
        return $this->resourceId;
    }

    public function setResourceId(?Resource $resourceId): static
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getChildId(): ?User
    {
        return $this->childId;
    }

    public function setChildId(?User $childId): static
    {
        $this->childId = $childId;

        return $this;
    }

    public function getOpenCount(): ?int
    {
        return $this->openCount;
    }

    public function setOpenCount(?int $openCount): static
    {
        $this->openCount = $openCount;

        return $this;
    }

    public function isCompleted(): ?bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(?bool $isCompleted): static
    {
        $this->isCompleted = $isCompleted;

        return $this;
    }
}
