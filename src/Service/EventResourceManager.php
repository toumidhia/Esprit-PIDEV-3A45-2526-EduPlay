<?php
// src/Service/EventResourceManager.php

namespace App\Service;

use App\Entity\EventResource;

class EventResourceManager
{
    public function validate(EventResource $resource): bool
    {
        // Règle 1 : Le titre est obligatoire
        if (empty($resource->getTitle())) {
            throw new \InvalidArgumentException('Le titre de la ressource est obligatoire.');
        }

        // Règle 2 : Le type est obligatoire
        $type = $resource->getType();
        if (empty($type)) {
            throw new \InvalidArgumentException('Le type de ressource est obligatoire.');
        }

        // Règle 3 : Type doit être valide
        $validTypes = ['PDF', 'LINK', 'CHECKLIST', 'PLANNING'];
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException('Type de ressource invalide. Types acceptés : PDF, LINK, CHECKLIST, PLANNING.');
        }

        // Règle 4 : Champs obligatoires selon le type
        switch ($type) {
            case 'LINK':
                if (empty($resource->getUrl())) {
                    throw new \InvalidArgumentException('Pour une ressource de type LINK, l\'URL est obligatoire.');
                }
                break;
                
            case 'PDF':
                if (empty($resource->getFilePath())) {
                    throw new \InvalidArgumentException('Pour une ressource de type PDF, le fichier est obligatoire.');
                }
                break;
                
            case 'CHECKLIST':
            case 'PLANNING':
                if (empty($resource->getContext())) {
                    throw new \InvalidArgumentException('Pour une ressource de type CHECKLIST/PLANNING, le contenu est obligatoire.');
                }
                break;
        }

        return true;
    }
}