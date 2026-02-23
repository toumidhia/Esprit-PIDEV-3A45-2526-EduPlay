<?php
// src/Controller/CalendarExportController.php

namespace App\Controller;

use App\Entity\SchoolEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class CalendarExportController extends AbstractController
{
    #[Route('/event/{id}/calendar/ics', name: 'event_calendar_ics', methods: ['GET'])]
    public function exportIcs(SchoolEvent $event): Response
    {
        $icsContent = $this->generateIcsContent($event);
        
        $response = new Response($icsContent);
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->sanitizeFilename($event->getTitle()) . '.ics'
        ));
        
        return $response;
    }
    
    #[Route('/event/{id}/calendar/google', name: 'event_calendar_google', methods: ['GET'])]
    public function redirectGoogle(SchoolEvent $event): Response
    {
        $start = $event->getStartDate()->format('Ymd\THis');
        $end = $event->getEndDate()->format('Ymd\THis');
        
        $params = [
            'action' => 'TEMPLATE',
            'text' => $event->getTitle(),
            'dates' => $start . '/' . $end,
            'details' => $event->getDescription(),
            'location' => $event->getLocation() ?? '',
            'sf' => 'true',
            'output' => 'xml',
        ];
        
        $url = 'https://www.google.com/calendar/render?' . http_build_query($params);
        
        return $this->redirect($url);
    }
    
    #[Route('/event/{id}/calendar/outlook', name: 'event_calendar_outlook', methods: ['GET'])]
    public function redirectOutlook(SchoolEvent $event): Response
    {
        $start = $event->getStartDate()->format('Y-m-d\TH:i:s');
        $end = $event->getEndDate()->format('Y-m-d\TH:i:s');
        
        $params = [
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'startdt' => $start,
            'enddt' => $end,
            'subject' => $event->getTitle(),
            'body' => $event->getDescription(),
            'location' => $event->getLocation() ?? '',
        ];
        
        $url = 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query($params);
        
        return $this->redirect($url);
    }
    
    private function generateIcsContent(SchoolEvent $event): string
    {
        $uid = uniqid('event_' . $event->getId() . '_', true) . '@eduplay.com';
        
        $startDate = $event->getStartDate()->format('Ymd\THis');
        $endDate = $event->getEndDate()->format('Ymd\THis');
        $created = (new \DateTime())->format('Ymd\THis');
        
        $title = $this->escapeIcsText($event->getTitle());
        $description = $this->escapeIcsText($event->getDescription());
        $location = $this->escapeIcsText($event->getLocation() ?? '');
        
        $ics = [];
        $ics[] = 'BEGIN:VCALENDAR';
        $ics[] = 'VERSION:2.0';
        $ics[] = 'PRODID:-//EduPlay//FR';
        $ics[] = 'CALSCALE:GREGORIAN';
        $ics[] = 'METHOD:PUBLISH';
        $ics[] = 'BEGIN:VEVENT';
        $ics[] = 'UID:' . $uid;
        $ics[] = 'DTSTART:' . $startDate;
        $ics[] = 'DTEND:' . $endDate;
        $ics[] = 'DTSTAMP:' . $created;
        $ics[] = 'CREATED:' . $created;
        $ics[] = 'LAST-MODIFIED:' . $created;
        $ics[] = 'SUMMARY:' . $title;
        $ics[] = 'DESCRIPTION:' . $description;
        
        if (!empty($location)) {
            $ics[] = 'LOCATION:' . $location;
        }
        
        $ics[] = 'STATUS:CONFIRMED';
        $ics[] = 'SEQUENCE:0';
        $ics[] = 'TRANSP:OPAQUE';
        $ics[] = 'END:VEVENT';
        $ics[] = 'END:VCALENDAR';
        
        return implode("\r\n", $ics);
    }
    
    private function escapeIcsText(string $text): string
    {
        $text = str_replace(["\r\n", "\n", "\r"], '\\n', $text);
        $text = str_replace([',', ';', '\\'], ['\,', '\;', '\\\\'], $text);
        
        return $text;
    }
    
    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-z0-9_-]/i', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        
        return trim($filename, '_');
    }
}