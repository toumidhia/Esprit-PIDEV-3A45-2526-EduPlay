<?php


namespace App\Entity;

use App\Repository\ResourceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
#[Vich\Uploadable]
class Resource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est requis')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9\s.,!?À-ÿ\-]+$/u',
        message: 'Le titre ne peut contenir que des lettres, chiffres, espaces et ponctuation de base'
    )]
    private string $title;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'auteur est requis")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le nom de l'auteur doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom de l'auteur ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s\-\.\',]+$/u',
        message: "Le nom de l'auteur ne peut contenir que des lettres, espaces, tirets, points, virgules et apostrophes"
    )]
    private string $author;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: "Le résumé ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $summary = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfFile = null;

    #[Vich\UploadableField(mapping: 'resource_cover', fileNameProperty: 'coverImage')]
    private ?File $coverImageFile = null;

    #[Vich\UploadableField(mapping: 'resource_pdf', fileNameProperty: 'pdfFile')]
    private ?File $pdfFileFile = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le type est requis')]
    #[Assert\Choice(
        choices: ['Livre', 'Magazine', 'Journal', 'Manuel'],
        message: 'Choisissez un type valide parmi {{ choices }}'
    )]
    private string $type;

    #[ORM\Column]
    #[Assert\NotBlank(message: "L'âge minimum est requis")]
    #[Assert\Range(
        min: 3,
        max: 18,
        notInRangeMessage: "L'âge minimum doit être entre {{ min }} et {{ max }} ans"
    )]
    private int $minAge;

    #[ORM\Column]
    #[Assert\NotBlank(message: "L'âge maximum est requis")]
    #[Assert\Range(
        min: 3,
        max: 18,
        notInRangeMessage: "L'âge maximum doit être entre {{ min }} et {{ max }} ans"
    )]
    private int $maxAge;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La langue est requise')]
    #[Assert\Choice(
        choices: ['Français', 'Anglais', 'Arabe', 'Espagnol'],
        message: 'Choisissez une langue valide parmi {{ choices }}'
    )]
    private string $language;

    #[ORM\ManyToOne(targetEntity: Library::class)]
    #[ORM\JoinColumn(name: 'library_id_id', nullable: false)]
    #[Assert\NotNull(message: "La bibliothèque est requise")]
    private Library $libraryId;

    // Méthodes de validation personnalisée (pour vérifier minAge < maxAge)
    #[Assert\Callback]
    public function validateAges(mixed $context): void
    {
        if ($this->minAge > $this->maxAge) {
            $context->buildViolation("L'âge maximum doit être supérieur ou égal à l'âge minimum")
                    ->atPath('maxAge')
                    ->addViolation();
        }
    }


    public function getId(): ?int
    {
        return $this->id;
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

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): static
    {
        $this->summary = $summary;
        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;
        return $this;
    }

    public function setCoverImageFile(?File $file = null): static
    {
        $this->coverImageFile = $file;
        return $this;
    }

    public function getCoverImageFile(): ?File
    {
        return $this->coverImageFile;
    }

    public function getPdfFile(): ?string
    {
        return $this->pdfFile;
    }

    public function setPdfFile(?string $pdfFile): static
    {
        $this->pdfFile = $pdfFile;
        return $this;
    }

    public function setPdfFileFile(?File $file = null): static
    {
        $this->pdfFileFile = $file;
        return $this;
    }

    public function getPdfFileFile(): ?File
    {
        return $this->pdfFileFile;
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

    public function getMinAge(): int
    {
        return $this->minAge;
    }

    public function setMinAge(int $minAge): static
    {
        $this->minAge = $minAge;
        return $this;
    }

    public function getMaxAge(): int
    {
        return $this->maxAge;
    }

    public function setMaxAge(int $maxAge): static
    {
        $this->maxAge = $maxAge;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;
        return $this;
    }

    public function getLibraryId(): Library  // CHANGÉ: getLibrary -> getLibraryId
    {
        return $this->libraryId;
    }

    public function setLibraryId(Library $libraryId): static  // CHANGÉ: setLibrary -> setLibraryId
    {
        $this->libraryId = $libraryId;
        return $this;
    }
}