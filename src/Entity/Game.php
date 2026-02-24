<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Game name is required.')]
    #[Assert\Length(min: 3, minMessage: 'Minimum 3 characters required.')]
    #[Assert\Regex(
        pattern: '/^[\p{L}\s]+$/u',
        message: 'The name must contain letters only.'
    )]
    private ?string $name = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please select a level.')]
    private ?Level $idLevel = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Game type is required.')]
    #[Assert\Regex(
        pattern: '/^[\p{L}\s]+$/u',
        message: 'The type must contain letters only.'
    )]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Description is required.')]
    #[Assert\Length(min: 5, minMessage: 'Minimum 5 characters required.')]
    #[Assert\Regex(
        pattern: '/^[\p{L}\s]+$/u',
        message: 'Description must contain letters and spaces only.'
    )]
    private ?string $description = null;

   #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;




    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getIdLevel(): ?Level
    {
        return $this->idLevel;
    }

    public function setIdLevel(?Level $idLevel): static
    {
        $this->idLevel = $idLevel;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }
}
