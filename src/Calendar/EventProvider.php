<?php
// src/Calendar/EventProvider.php

namespace App\Calendar;

use App\Repository\SchoolEventRepository;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EventProvider implements EventSubscriberInterface
{
    public function __construct(
        private SchoolEventRepository $eventRepository,
        private UrlGeneratorInterface $router
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar): void
    {
        $start = $calendar->getStart();
        $end = $calendar->getEnd();

        // Récupérer les événements entre les dates
        $events = $this->eventRepository->createQueryBuilder('e')
            ->where('e.startDate BETWEEN :start AND :end')
            ->orWhere('e.endDate BETWEEN :start AND :end')
            ->orWhere('e.startDate <= :start AND e.endDate >= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($events as $schoolEvent) {
            // Créer un événement pour le calendrier
            $event = new Event(
                $schoolEvent->getTitle(),
                $schoolEvent->getStartDate(),
                $schoolEvent->getEndDate() // Si null, événement d'une journée
            );

            // Ajouter l'URL pour le clic
            $event->setOptions([
                'url' => $this->router->generate('admin_event_show', [
                    'id' => $schoolEvent->getId()
                ]),
                'backgroundColor' => $schoolEvent->getStartDate() > new \DateTime() ? '#4f46e5' : '#6b7280',
                'borderColor' => $schoolEvent->getStartDate() > new \DateTime() ? '#4f46e5' : '#6b7280',
                'textColor' => '#ffffff',
            ]);

            // Ajouter l'événement au calendrier
            $calendar->addEvent($event);
        }
    }
}