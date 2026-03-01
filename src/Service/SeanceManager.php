<?php

namespace App\Service;

use App\Entity\Seance;

class SeanceManager
{
    public function validate(Seance $seance): bool
    {
        if ($seance->getStartTime() !== null && $seance->getEndTime() !== null && $seance->getEndTime() <= $seance->getStartTime()) {
            throw new \InvalidArgumentException("La date de fin d'un événement doit être postérieure à la date de début.");
        }

        $validStatuses = ['scheduled', 'ongoing', 'completed', 'cancelled'];
        if (!in_array($seance->getStatus(), $validStatuses, true)) {
            throw new \InvalidArgumentException("Statut invalide.");
        }

        return true;
    }
}
