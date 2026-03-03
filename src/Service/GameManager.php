<?php

namespace App\Service;

use App\Entity\Game;

class GameManager
{
    public function validate(Game $game): bool
    {
        // Validation du nom
        if (empty($game->getName()) || strlen($game->getName()) < 3) {
            throw new \InvalidArgumentException('Le nom du jeu est obligatoire et doit contenir au moins 3 caractères.');
        }
        if (!preg_match('/^[\p{L}\s]+$/u', $game->getName())) {
            throw new \InvalidArgumentException('Le nom doit contenir uniquement des lettres.');
        }

        // Validation du type
        if (empty($game->getType())) {
            throw new \InvalidArgumentException('Le type du jeu est obligatoire.');
        }
        if (!preg_match('/^[\p{L}\s]+$/u', $game->getType())) {
            throw new \InvalidArgumentException('Le type doit contenir uniquement des lettres.');
        }

        // Validation de la description
        if (empty($game->getDescription()) || strlen($game->getDescription()) < 5) {
            throw new \InvalidArgumentException('La description est obligatoire et doit contenir au moins 5 caractères.');
        }
        if (!preg_match('/^[\p{L}\s]+$/u', $game->getDescription())) {
            throw new \InvalidArgumentException('La description doit contenir uniquement des lettres et des espaces.');
        }

        // Validation du niveau
        if ($game->getIdLevel() === null) {
            throw new \InvalidArgumentException('Le niveau est obligatoire.');
        }

        return true;
    }
}