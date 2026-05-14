<?php
// src/Service/EventRegistrationManager.php

namespace App\Service;

use App\Entity\EventRegistration;

class EventRegistrationManager
{
    public function validate(EventRegistration $registration): bool
    {
        // Règle 1 : Nom de l'enfant obligatoire
        if (empty($registration->getChildFullName())) {
            throw new \InvalidArgumentException('Le nom de l\'enfant est obligatoire.');
        }

        // Règle 2 : Lié à un événement
        if (null === $registration->getEvent()) {
            throw new \InvalidArgumentException('L\'inscription doit être liée à un événement.');
        }

        // Règle 3 : Lié à un parent
        if (null === $registration->getParent()) {
            throw new \InvalidArgumentException('L\'inscription doit être liée à un parent.');
        }

        // Règle 4 : Statut valide
        $validStatuses = ['PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'];
        $status = $registration->getStatus();
        
        if (empty($status)) {
            throw new \InvalidArgumentException('Le statut est obligatoire.');
        }
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException('Statut invalide. Valeurs acceptées : PENDING, APPROVED, REJECTED, CANCELLED.');
        }

        // Règle 5 : Date d'inscription pas dans le futur
        if ($registration->getRegisteredAt() > new \DateTime()) {
            throw new \InvalidArgumentException('La date d\'inscription ne peut pas être dans le futur.');
        }

        return true;
    }
}