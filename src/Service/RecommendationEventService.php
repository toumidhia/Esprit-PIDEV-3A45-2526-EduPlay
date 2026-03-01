<?php
// src/Service/RecommendationEventService.php

namespace App\Service;

use App\Entity\User;
use App\Entity\SchoolEvent;
use App\Repository\EventRegistrationRepository;
use App\Repository\SchoolEventRepository;

class RecommendationEventService
{
    public function __construct(
        private EventRegistrationRepository $registrationRepo,
        private SchoolEventRepository $eventRepo,
    ) {}

    public function getRecommendationsForParent(User $parent, int $limit = 3): array
    {
        // 1. Récupérer toutes les inscriptions du parent
        $registrations = $this->registrationRepo->findBy([
            'parent' => $parent
        ]);

        if (empty($registrations)) {
            // Pas d'historique → recommandations populaires
            return $this->getPopularEvents($limit);
        }

        // 2. Analyser les préférences
        $preferences = $this->analyzePreferences($registrations);

        // 3. Trouver des événements correspondants
        return $this->findMatchingEvents($preferences, $limit);
    }

    private function analyzePreferences(array $registrations): array
    {
        $preferences = [
            'eventTypes' => [],
            'locations' => [],
            'childAges' => [],
            'total' => count($registrations)
        ];

        foreach ($registrations as $reg) {
            $event = $reg->getEvent();
            if (!$event) continue;
            
            // Analyser les types
            $type = $this->categorizeEvent($event);
            if (!isset($preferences['eventTypes'][$type])) {
                $preferences['eventTypes'][$type] = 0;
            }
            $preferences['eventTypes'][$type]++;

            // Analyser les lieux
            if ($event->getLocation()) {
                $city = $this->extractCity($event->getLocation());
                if (!isset($preferences['locations'][$city])) {
                    $preferences['locations'][$city] = 0;
                }
                $preferences['locations'][$city]++;
            }
        }

        arsort($preferences['eventTypes']);
        arsort($preferences['locations']);

        return $preferences;
    }

    private function findMatchingEvents(array $preferences, int $limit): array
    {
        $qb = $this->eventRepo->createQueryBuilder('e')
            ->where('e.startDate > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit * 2);

        $events = $qb->getQuery()->getResult();
        $scoredEvents = [];

        foreach ($events as $event) {
            $score = 0;
            
            $eventType = $this->categorizeEvent($event);
            if (isset($preferences['eventTypes'][$eventType])) {
                $score += $preferences['eventTypes'][$eventType] * 3;
            }

            if ($event->getLocation()) {
                $city = $this->extractCity($event->getLocation());
                if (isset($preferences['locations'][$city])) {
                    $score += $preferences['locations'][$city] * 2;
                }
            }

            $score += $this->getPopularityScore($event);

            $scoredEvents[] = [
                'event' => $event,
                'score' => $score,
                'reasons' => $this->getRecommendationReasons($event, $preferences)
            ];
        }

        usort($scoredEvents, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scoredEvents, 0, $limit);
    }

    private function getPopularEvents(int $limit): array
    {
        $qb = $this->eventRepo->createQueryBuilder('e')
            ->select('e, COUNT(r.id) as registrationCount')
            ->leftJoin('e.registrations', 'r')
            ->where('e.startDate > :now')
            ->setParameter('now', new \DateTime())
            ->groupBy('e.id')
            ->orderBy('registrationCount', 'DESC')
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();
        
        $popularEvents = [];
        foreach ($results as $result) {
            $popularEvents[] = [
                'event' => $result[0],
                'score' => (int)$result['registrationCount'],
                'reasons' => ['Populaire auprès des familles']
            ];
        }

        return $popularEvents;
    }

    private function categorizeEvent(SchoolEvent $event): string
    {
        $title = strtolower((string)$event->getTitle());
        $desc = strtolower((string)$event->getDescription());

        if (str_contains($title, 'musée') || str_contains($desc, 'musée')) return 'culturel';
        if (str_contains($title, 'sport') || str_contains($desc, 'sport')) return 'sportif';
        if (str_contains($title, 'atelier') || str_contains($desc, 'atelier')) return 'atelier';
        if (str_contains($title, 'jeu') || str_contains($desc, 'jeu')) return 'ludique';
        
        return 'autre';
    }

    private function extractCity(?string $location): string
    {
        if (!$location) return 'inconnu';
        if (preg_match('/\b\d{5}\b/', $location, $matches)) return $matches[0];
        
        $parts = explode(' ', trim($location));
        return $parts[0] ?? 'inconnu';
    }


    private function getPopularityScore(SchoolEvent $event): int
    {
        return count($event->getRegistrations()) * 2;
    }

    private function getRecommendationReasons(SchoolEvent $event, array $preferences): array
    {
        $reasons = [];
        $eventType = $this->categorizeEvent($event);
        
        if (isset($preferences['eventTypes'][$eventType])) {
            $reasons[] = "Vous aimez les événements {$eventType}s";
        }

        if ($event->getLocation()) {
            $city = $this->extractCity($event->getLocation());
            if (isset($preferences['locations'][$city])) {
                $reasons[] = "Près de chez vous";
            }
        }

        return !empty($reasons) ? $reasons : ["Populaire auprès des familles"];
    }
}