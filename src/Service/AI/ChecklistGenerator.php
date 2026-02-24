<?php
// src/Service/AI/ChecklistGenerator.php

namespace App\Service\AI;

use App\Entity\SchoolEvent;

class ChecklistGenerator
{
    public function __construct(
        private GeminiClient $geminiClient
    ) {}

    public function generateChecklist(SchoolEvent $event): array
    {
        $prompt = $this->buildPrompt($event);
        $content = $this->geminiClient->generateContent($prompt, 1000);

        return [
            'content' => $content,
            'title' => '📋 Checklist - ' . $event->getTitle() . ' (' . $event->getStartDate()->format('d/m/Y') . ')'
        ];
    }

    private function buildPrompt(SchoolEvent $event): string
    {
        $duration = $this->calculateDuration($event);
        $date = $event->getStartDate()->format('d/m/Y');
        $startTime = $event->getStartDate()->format('H:i');
        $endTime = $event->getEndDate()->format('H:i');
        
        return "Génère une LISTE POUR LES PARENTS (à préparer avec leur enfant) pour l'événement suivant :

    Événement: {$event->getTitle()}
    Date: $date
    Horaires: $startTime à $endTime
    Lieu: {$event->getLocation()}
    Description: {$event->getDescription()}

    IMPORTANT: Ce document sera imprimé et donné aux parents. L'enfant doit le ramener le jour J.

    Format attendu (avec des cases à cocher) :

    =============================================
    LISTE À PRÉPARER POUR {$event->getTitle()}
    =============================================

    👕 TENUE À PRÉVOIR :
    ☐ [ ] Vêtements confortables
    ☐ [ ] Chaussures adaptées à la marche
    ☐ [ ] Casquette (si sortie en extérieur)
    ☐ [ ] Veste (selon météo)

    🍱 À METTRE DANS LE SAC :
    ☐ [ ] Pique-nique (préciser si besoin)
    ☐ [ ] Bouteille d'eau (facultatif)
    ☐ [ ] Petit goûter (facultatif)
    ☐ [ ] Mouchoirs en papier

    📋 DOCUMENTS À NE PAS OUBLIER :
    ☐ [ ] Cette liste (à ramener signée)
    ☐ [ ] Autorisation parentale (si envoyée)
    ☐ [ ] Carte d'identité (si nécessaire)
    ☐ [ ] Ticket d'entrée (si fourni)

    ⚠️ INFORMATIONS IMPORTANTES :
    • Rendez-vous à $startTime au lieu indiqué
    • Fin de l'événement à $endTime
    • Prévenir en cas d'allergie ou de problème médical
    • [Autre info spécifique à l'événement]

    📞 NUMÉROS UTILES :
    • Contact sur place : 22222222
    • En cas d'urgence : 99999999

    =============================================
    À ramener le jour de l'événement !

    Génère cette liste de façon claire, avec des emojis et des cases à cocher. Sois très pratique et concret :";
    }

    private function calculateDuration(SchoolEvent $event): string
    {
        $interval = $event->getStartDate()->diff($event->getEndDate());
        $hours = $interval->h + ($interval->days * 24);
        $minutes = $interval->i;
        
        if ($hours > 0) {
            return $hours . ' heure(s) et ' . $minutes . ' minute(s)';
        }
        return $minutes . ' minute(s)';
    }
}