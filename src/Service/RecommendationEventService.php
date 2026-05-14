<?php
// src/Service/RecommendationEventService.php

namespace App\Service;

use App\Entity\User;
use App\Entity\SchoolEvent;
use App\Entity\EventRegistration;
use App\Repository\EventRegistrationRepository;
use App\Repository\SchoolEventRepository;

class RecommendationEventService
{
    public function __construct(
        private EventRegistrationRepository $registrationRepo,
        private SchoolEventRepository $eventRepo,
    ) {}

    /**
     * @param User $parent
     * @param int $limit
     * @return array<array{event: SchoolEvent, score: int, reasons: array<string>}>
     */
    public function getRecommendationsForParent(User $parent, int $limit = 3): array
    {
        $registrations = $this->registrationRepo->findBy([
            'parent' => $parent
        ]);

        if (empty($registrations)) {
            return $this->getPopularEvents($limit);
        }

        $preferences = $this->analyzePreferences($registrations);
        return $this->findMatchingEvents($preferences, $limit);
    }

    /**
     * @param array<EventRegistration> $registrations
     * @return array{eventTypes: array<string,int>, locations: array<string,int>, childAges: array<string,int>, total: int}
     */
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
            
            $type = $this->categorizeEvent($event);
            $preferences['eventTypes'][$type] = ($preferences['eventTypes'][$type] ?? 0) + 1;

            if ($event->getLocation()) {
                $city = $this->extractCity($event->getLocation());
                $preferences['locations'][$city] = ($preferences['locations'][$city] ?? 0) + 1;
            }
        }

        arsort($preferences['eventTypes']);
        arsort($preferences['locations']);

        return $preferences;
    }

    /**
     * @param array{eventTypes: array<string,int>, locations: array<string,int>, childAges: array<string,int>, total: int} $preferences
     * @param int $limit
     * @return array<array{event: SchoolEvent, score: int, reasons: array<string>}>
     */
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

    /**
     * @param int $limit
     * @return array<array{event: SchoolEvent, score: int, reasons: array<string>}>
     */
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
            /** @var SchoolEvent $event */
            $event = $result[0];
            $popularEvents[] = [
                'event' => $event,
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

    /**
     * @param SchoolEvent $event
     * @param array{eventTypes: array<string,int>, locations: array<string,int>, childAges: array<string,int>, total: int} $preferences
     * @return array<string>
     */
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

        // ✅ Version qui ne déclenche PAS l'erreur PHPStan
        if ($reasons !== []) {
            return $reasons;
        }
        
        return ["Populaire auprès des familles"];
    }
}