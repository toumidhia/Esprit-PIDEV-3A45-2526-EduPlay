<?php
// src/Service/RecommendationService.php

namespace App\Service;

use App\Entity\User;
use App\Entity\SchoolEvent;
use App\Repository\EventRegistrationRepository;
use App\Repository\SchoolEventRepository;
use Doctrine\ORM\EntityManagerInterface;

class RecommendationEventService
{
    public function __construct(
        private EventRegistrationRepository $registrationRepo,
        private SchoolEventRepository $eventRepo,
        private EntityManagerInterface $em
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
        $recommendations = $this->findMatchingEvents($preferences, $limit);

        return $recommendations;
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
            
            // Analyser les types (basé sur le titre/mots-clés)
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

            // Analyser l'âge de l'enfant inscrit
            $childAge = $this->getChildAge($reg);
            if ($childAge) {
                $ageGroup = $this->getAgeGroup($childAge);
                if (!isset($preferences['childAges'][$ageGroup])) {
                    $preferences['childAges'][$ageGroup] = 0;
                }
                $preferences['childAges'][$ageGroup]++;
            }
        }

        // Trier par fréquence
        arsort($preferences['eventTypes']);
        arsort($preferences['locations']);
        arsort($preferences['childAges']);

        return $preferences;
    }

    private function findMatchingEvents(array $preferences, int $limit): array
    {
        $qb = $this->eventRepo->createQueryBuilder('e')
            ->where('e.startDate > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit * 2); // On prend plus pour pouvoir filtrer

        $events = $qb->getQuery()->getResult();
        $scoredEvents = [];

        foreach ($events as $event) {
            $score = 0;
            
            // Score basé sur le type d'événement
            $eventType = $this->categorizeEvent($event);
            if (isset($preferences['eventTypes'][$eventType])) {
                $score += $preferences['eventTypes'][$eventType] * 3;
            }

            // Score basé sur la localisation
            if ($event->getLocation()) {
                $city = $this->extractCity($event->getLocation());
                if (isset($preferences['locations'][$city])) {
                    $score += $preferences['locations'][$city] * 2;
                }
            }

            // Score basé sur la popularité (si pas d'historique)
            $popularityScore = $this->getPopularityScore($event);
            $score += $popularityScore;

            $scoredEvents[] = [
                'event' => $event,
                'score' => $score,
                'reasons' => $this->getRecommendationReasons($event, $preferences)
            ];
        }

        // Trier par score décroissant
        usort($scoredEvents, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($scoredEvents, 0, $limit);
    }

    private function getPopularEvents(int $limit): array
    {
        // Événements les plus populaires (avec le plus d'inscriptions)
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
                'score' => $result['registrationCount'],
                'reasons' => ['populaire']
            ];
        }

        return $popularEvents;
    }

    private function categorizeEvent(SchoolEvent $event): string
    {
        $title = strtolower($event->getTitle());
        $desc = strtolower($event->getDescription());

        if (strpos($title, 'musée') !== false || strpos($desc, 'musée') !== false) {
            return 'culturel';
        }
        if (strpos($title, 'sport') !== false || strpos($desc, 'sport') !== false) {
            return 'sportif';
        }
        if (strpos($title, 'atelier') !== false || strpos($desc, 'atelier') !== false) {
            return 'atelier';
        }
        if (strpos($title, 'jeu') !== false || strpos($desc, 'jeu') !== false) {
            return 'ludique';
        }
        
        return 'autre';
    }

    private function extractCity(?string $location): string
    {
        if (!$location) return 'inconnu';
        
        // Extraction simple (premier mot ou code postal)
        if (preg_match('/\b\d{5}\b/', $location, $matches)) {
            return $matches[0]; // Code postal
        }
        
        $parts = explode(' ', trim($location));
        return $parts[0] ?? 'inconnu';
    }

    private function getChildAge($registration): ?int
    {
        // À adapter selon comment l'âge de l'enfant est stocké
        // Si tu as une entité Child, tu peux calculer l'âge
        return null;
    }

    private function getAgeGroup(?int $age): string
    {
        if (!$age) return 'inconnu';
        if ($age < 6) return 'maternelle';
        if ($age < 10) return 'primaire';
        return 'college';
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

        if (empty($reasons)) {
            $reasons[] = "Populaire auprès des familles";
        }

        return $reasons;
    }
}