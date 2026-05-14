<?php
// src/Service/EventManager.php

namespace App\Service;

use App\Entity\SchoolEvent;

class EventManager
{
    public function validate(SchoolEvent $event): bool
    {
        // Règle 1 : Le titre est obligatoire
        if (empty($event->getTitle())) {
            throw new \InvalidArgumentException('Le titre de l\'événement est obligatoire.');
        }

        // Règle 2 : La date de fin doit être postérieure à la date de début
        if ($event->getEndDate() <= $event->getStartDate()) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        return true;
    }
}