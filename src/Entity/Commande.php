<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $idUser = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $idProduct = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateCommande = null;

    #[ORM\Column]
    private ?int $totalAmount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUser(): ?User
    {
        return $this->idUser;
    }

    public function setIdUser(?User $idUser): static
    {
        $this->idUser = $idUser;

        return $this;
    }

    public function getIdProduct(): ?Product
    {
        return $this->idProduct;
    }

    public function setIdProduct(?Product $idProduct): static
    {
        $this->idProduct = $idProduct;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getDateCommande(): ?\DateTime
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTime $dateCommande): static
    {
        $this->dateCommande = $dateCommande;

        return $this;
    }

    public function getTotalAmount(): ?int
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(int $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }
}
