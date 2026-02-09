<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $name = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le prix est obligatoire.')]
    #[Assert\Type(type: 'float', message: 'Le prix doit être un nombre.')]
    #[Assert\PositiveOrZero(message: 'Le prix doit être positif ou nul.')]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(min: 5, max: 255, minMessage: 'La description doit contenir au moins {{ limit }} caractères.', maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Veuillez indiquer la disponibilité.')]
    private ?bool $availability = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $picture = null;

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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

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

    public function isAvailability(): ?bool
    {
        return $this->availability;
    }

    public function setAvailability(bool $availability): static
    {
        $this->availability = $availability;

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;

        return $this;
    }
}
