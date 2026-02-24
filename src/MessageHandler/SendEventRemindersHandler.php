<?php
// src/MessageHandler/SendEventRemindersHandler.php

namespace App\MessageHandler;

use App\Message\SendEventRemindersMessage;
use App\Repository\EventRegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsMessageHandler]
class SendEventRemindersHandler
{
    public function __construct(
        private EventRegistrationRepository $registrationRepo,
        private MailerInterface $mailer,
        private Environment $twig,
        private EntityManagerInterface $em
    ) {}

    public function __invoke(SendEventRemindersMessage $message): void
    {
        $hoursBefore = $message->getHoursBefore();
        $now = new \DateTime();
        
        $startWindow = (clone $now)->modify("+{$hoursBefore} hours -1 hour");
        $endWindow = (clone $now)->modify("+{$hoursBefore} hours +1 hour");
        
        $registrations = $this->registrationRepo->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('e.startDate BETWEEN :start AND :end')
            ->andWhere('r.reminderSent = false OR r.reminderSent IS NULL')
            ->setParameter('start', $startWindow)
            ->setParameter('end', $endWindow)
            ->getQuery()
            ->getResult();
        
        foreach ($registrations as $registration) {
            $this->sendReminder($registration);
            $registration->setReminderSent(true);
            $registration->setReminderSentAt(new \DateTime());
            $this->em->persist($registration);
        }
        
        $this->em->flush();
    }
    
    private function sendReminder($registration): void
    {
        $parent = $registration->getParent();
        $event = $registration->getEvent();
        
        $htmlContent = $this->twig->render('emails/event_reminder.html.twig', [
            'parent' => $parent,
            'event' => $event,
            'registration' => $registration,
            'childName' => $registration->getChildFullName(),
        ]);
        
        $email = (new Email())
            ->from('no-reply@eduplay.com')
            ->to($parent->getEmail())
            ->subject('📅 Rappel : ' . $event->getTitle() . ' aujourd\'hui !')
            ->html($htmlContent);
        
        if ($registration->getQrCodePath()) {
            $qrCodePath = $this->getProjectDir() . '/public' . $registration->getQrCodePath();
            if (file_exists($qrCodePath)) {
                $email->attachFromPath($qrCodePath, 'ticket.png', 'image/png');
            }
        }
        
        $this->mailer->send($email);
    }
    
    private function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }
}