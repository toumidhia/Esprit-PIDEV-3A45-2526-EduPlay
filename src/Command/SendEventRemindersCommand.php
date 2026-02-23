<?php
// src/Command/SendEventRemindersCommand.php

namespace App\Command;

use App\Entity\EventRegistration;
use App\Repository\EventRegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsCommand(
    name: 'app:send-event-reminders',
    description: 'Envoie des rappels par email avant les événements',
)]
class SendEventRemindersCommand extends Command
{
    public function __construct(
        private EventRegistrationRepository $registrationRepository,
        private MailerInterface $mailer,
        private Environment $twig,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('hours', null, InputOption::VALUE_OPTIONAL, 'Nombre d\'heures avant l\'événement', 24)
            ->addOption('range', null, InputOption::VALUE_OPTIONAL, 'Fenêtre de recherche en heures', 2);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $hoursBefore = (int) $input->getOption('hours');
        $range = (int) $input->getOption('range');
        
        $now = new \DateTime();
        $startWindow = (clone $now)->modify("+{$hoursBefore} hours -{$range} hours");
        $endWindow = (clone $now)->modify("+{$hoursBefore} hours +{$range} hours");
        
        $io->note(sprintf(
            'Recherche des événements commençant dans ~%dh (±%dh) entre %s et %s',
            $hoursBefore,
            $range,
            $startWindow->format('d/m/Y H:i'),
            $endWindow->format('d/m/Y H:i')
        ));
        
        // Trouver toutes les inscriptions dont l'événement commence dans la fenêtre
        $registrations = $this->registrationRepository->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('e.startDate BETWEEN :start AND :end')
            ->andWhere('r.reminderSent = false OR r.reminderSent IS NULL')
            ->setParameter('start', $startWindow)
            ->setParameter('end', $endWindow)
            ->getQuery()
            ->getResult();
        
        $count = 0;
        foreach ($registrations as $registration) {
            try {
                $this->sendReminder($registration);
                $registration->setReminderSent(true);
                $registration->setReminderSentAt(new \DateTime());
                $this->em->persist($registration);
                $count++;
            } catch (\Exception $e) {
                $io->error('Erreur pour inscription #' . $registration->getId() . ' : ' . $e->getMessage());
            }
        }
        
        $this->em->flush();
        
        $io->success("$count rappels envoyés avec succès !");
        
        return Command::SUCCESS;
    }
    
    private function sendReminder(EventRegistration $registration): void
    {
        $parent = $registration->getParent();
        $event = $registration->getEvent();
        
        if (!$parent || !$parent->getEmail()) {
            throw new \Exception('Parent sans email');
        }
        
        // Générer le contenu HTML
        $htmlContent = $this->twig->render('emails/event_reminder.html.twig', [
            'parent' => $parent,
            'event' => $event,
            'registration' => $registration,
            'childName' => $registration->getChildFullName(),
        ]);
        
        // Créer l'email
        $email = (new Email())
            ->from('no-reply@eduplay.com')
            ->to($parent->getEmail())
            ->subject('📅 Rappel : ' . $event->getTitle() . ' demain !')
            ->html($htmlContent);
        
        // Ajouter le ticket en pièce jointe si disponible
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