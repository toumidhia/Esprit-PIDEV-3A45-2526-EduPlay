<?php
// src/Entity/EventResource.php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Repository\EventResourceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventResourceRepository::class)]
class EventResource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $type;  // plus de ?

    #[ORM\Column(length: 255)]
    private string $title; // plus de ?

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $context = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePath = null; 

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt; // plus de ?

    #[ORM\ManyToOne(inversedBy: 'resources')]
    #[ORM\JoinColumn(nullable: false)]
    private SchoolEvent $event; // plus de ?

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getEvent(): SchoolEvent
    {
        return $this->event;
    }

    public function setEvent(SchoolEvent $event): static
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        $type = $this->getType();

        if ($type === 'LINK') {
            if (!$this->getUrl()) {
                $context->buildViolation("Pour une ressource de type LINK, l'URL est obligatoire.")
                    ->atPath('url')
                    ->addViolation();
            }
        }

        if ($type === 'PDF') {
            if (!$this->getFilePath()) {
                $context->buildViolation("Pour une ressource de type PDF, le fichier est obligatoire.")
                    ->atPath('filePath')
                    ->addViolation();
            }
        }

        if ($type === 'CHECKLIST' || $type === 'PLANNING') {
            if (!$this->getContext()) {
                $context->buildViolation("Pour CHECKLIST/PLANNING, le champ contenu est obligatoire.")
                    ->atPath('context')
                    ->addViolation();
            }
        }
    }
}