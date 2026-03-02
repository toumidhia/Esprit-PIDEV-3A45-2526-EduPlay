<?php
// src/Service/ResourceManager.php

namespace App\Service;

use App\Entity\Resource;

class ResourceManager
{
    private const VALID_TYPES = ['book', 'magazine', 'journal', 'manual'];

    public function validate(Resource $resource): bool
    {
        // Règle 1 : Le titre est obligatoire
        if (empty($resource->getTitle())) {
            throw new \InvalidArgumentException('Le titre est obligatoire');
        }

        // Règle 2 : L'auteur est obligatoire
        if (empty($resource->getAuthor())) {
            throw new \InvalidArgumentException('L\'auteur est obligatoire');
        }

        // Règle 3 : L'âge minimum ne peut pas être négatif
        if ($resource->getMinAge() < 0) {
            throw new \InvalidArgumentException('L\'âge minimum ne peut pas être négatif');
        }

        // Règle 4 : L'âge maximum doit être supérieur à l'âge minimum
        if ($resource->getMaxAge() <= $resource->getMinAge()) {
            throw new \InvalidArgumentException('L\'âge maximum doit être supérieur à l\'âge minimum');
        }

        // Règle 5 : Le type doit être valide
        if (!in_array(strtolower($resource->getType() ?? ''), self::VALID_TYPES)) {
            throw new \InvalidArgumentException('Le type doit être : book, magazine, journal ou manual');
        }

        // Règle 6 : La langue ne peut pas être vide
        if (empty($resource->getLanguage())) {
            throw new \InvalidArgumentException('La langue est obligatoire');
        }

        return true;
    }
}