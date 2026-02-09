<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La quantité est obligatoire.')]
    #[Assert\Positive(message: 'La quantité doit être positive.')]
    #[Assert\LessThanOrEqual(
        value: 100,
        message: 'La quantité ne peut pas dépasser {{ compared_value }}.'
    )]
    private ?int $quantity = null;

    #[ORM\Column(name: 'date_commande')]
    private ?\DateTimeInterface $dateCommande = null;

    #[ORM\Column(name: 'total_amount', type: 'float')]
    private ?float $totalAmount = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'Veuillez sélectionner un utilisateur.')]
    private ?User $idUser = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'id_product_id', referencedColumnName: 'id', nullable: false)]
    private ?Product $idProduct = null;

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
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

    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTimeInterface $dateCommande): static
    {
        $this->dateCommande = $dateCommande;
        return $this;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
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
}