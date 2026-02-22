<?php
// src/Service/AI/PlanningGenerator.php

namespace App\Service\AI;

use App\Entity\SchoolEvent;

class PlanningGenerator
{
    public function __construct(
        private GeminiClient $geminiClient
    ) {}

    public function generatePlanning(SchoolEvent $event): array
    {
        $prompt = $this->buildPrompt($event);
        $content = $this->geminiClient->generateContent($prompt, 1200);

        return [
            'content' => $content,
            'title' => '📅 Planning - ' . $event->getTitle() . ' (' . $event->getStartDate()->format('d/m/Y') . ')'
        ];
    }

   private function buildPrompt(SchoolEvent $event): string
    {
        $duration = $this->calculateDuration($event);
        $date = $event->getStartDate()->format('d/m/Y');
        $startTime = $event->getStartDate()->format('H:i');
        $endTime = $event->getEndDate()->format('H:i');
        
        return "Génère un PROGRAMME POUR ENFANTS (lisible et amusant) pour l'événement suivant :

    Événement: {$event->getTitle()}
    Date: $date
    Horaires: $startTime à $endTime
    Lieu: {$event->getLocation()}
    Description: {$event->getDescription()}

    IMPORTANT: Ce document sera donné aux enfants. Il doit être :
    - Coloré et amusant (avec des emojis)
    - Très simple à comprendre
    - Format A5 (petit format)
    - L'enfant pourra le ramener chez lui comme souvenir

    Format attendu :

    🎉 PROGRAMME DE LA JOURNÉE - {$event->getTitle()} 🎉

    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    🌟 MON PROGRAMME 🌟
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    🕘 $startTime - $startTime+30min
    • ACCUEIL
    • On se dit bonjour !
    • On fait connaissance

    🕙 $startTime+30min - 12:00
    • ACTIVITÉ PRINCIPALE 1
    • [description simple et amusante]
    • [petit jeu ou découverte]

    🕛 12:00 - 13:00
    • 🍱 PIQUE-NIQUE
    • On mange tous ensemble
    • On se repose un peu

    🕐 13:00 - 15:00
    • ACTIVITÉ PRINCIPALE 2
    • [description simple]
    • [jeu ou atelier]

    🕒 15:00 - 15:30
    • 🍪 GOÛTER
    • Petite pause gourmande

    🕓 15:30 - 16:30
    • JEUX COLLECTIFS
    • [description des jeux]

    🕔 $endTime-30min - $endTime
    • 👋 FIN DE LA JOURNÉE
    • On se dit au revoir
    • Distribution des souvenirs

    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    📝 À SAVOIR :
    • Pense à ramener cette feuille !
    • Habille-toi confortablement
    • Prévénons un adulte si tu ne te sens pas bien

    🎒 DANS TON SAC :
    • Ta bonne humeur 😊
    • Ton pique-nique (si pas fourni)
    • Ta gourde

    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    À très vite pour cette belle aventure !
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    Génère ce programme pour enfant de façon claire, amusante et adaptée (âge 6-10 ans) :";
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