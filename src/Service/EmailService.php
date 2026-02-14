<?php
// src/Service/EmailService.php

namespace App\Service;

use App\Entity\Course;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    private $mailer;
    private $logger;
    private $adminEmail;
    private $router;
    private $adminName;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        UrlGeneratorInterface $router,
        string $adminEmail = 'saadliwassieo@gmail.com',
        string $adminName = 'EduPlay'
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->router = $router;
        $this->adminEmail = $adminEmail;
        $this->adminName = $adminName;
    }

    /**
     * Envoie une notification aux parents pour un nouveau cours
     */
    /**
     * Envoie une notification aux parents pour un nouveau cours
     * VERSION ULTRA SIMPLE - Envoie uniquement à saadliwassieo@gmail.com
     */
    public function sendNewCourseNotification(Course $course, array $parents): void
    {
        $this->logger->info('=== ENVOI EMAIL DE TEST ===');

        // Générer l'URL du cours
        $courseUrl = $this->router->generate('app_course_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $teacher = $course->getTeacherId();

        try {
            // Créer un parent factice avec vos informations
            $fakeParent = new User();
            $fakeParent->setEmail('saadliwassieo@gmail.com');
            $fakeParent->setFirstName('Wassim');
            $fakeParent->setLastName('Saadli');

            // Envoyer UNIQUEMENT à vous, IGNORER la liste des parents
            $email = (new TemplatedEmail())
                ->from(new Address($this->adminEmail, $this->adminName))
                ->to('saadliwassieo@gmail.com')
                ->subject('🎓 Nouveau cours créé : ' . $course->getTitle())
                ->htmlTemplate('emails/new_course_notification.html.twig')
                ->context([
                    'course' => $course,
                    'parent' => $fakeParent,
                    'teacher' => $teacher,
                    'course_url' => $courseUrl,
                    'year' => date('Y')
                ]);

            $this->mailer->send($email);
            $this->logger->info('✓ Email de test envoyé à saadliwassieo@gmail.com');

        } catch (\Exception $e) {
            $this->logger->error('✗ Erreur: ' . $e->getMessage());
        }

        $this->logger->info('=== FIN ENVOI EMAIL DE TEST ===');
    }


    /**
     * Envoie une notification à un parent spécifique
     */
    public function sendCourseNotificationToParent(Course $course, User $parent): void
    {
        try {
            if (!$parent->getEmail()) {
                throw new \Exception('Le parent n\'a pas d\'adresse email');
            }

            $courseUrl = $this->router->generate('app_course_index', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $email = (new TemplatedEmail())
                ->from(new Address($this->adminEmail, 'EduPlay'))
                ->to(new Address($parent->getEmail(), $parent->getFirstName() . ' ' . $parent->getLastName()))
                ->subject('🎓 Nouveau cours disponible : ' . $course->getTitle())
                ->htmlTemplate('emails/new_course_notification.html.twig')
                ->context([
                    'course' => $course,
                    'parent' => $parent,
                    'teacher' => $course->getTeacherId(),
                    'course_url' => $courseUrl,
                    'year' => date('Y')
                ]);

            $this->mailer->send($email);
            $this->logger->info('Email individuel envoyé à: ' . $parent->getEmail());

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email individuel: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test de connexion SMTP
     */
    public function testConnection(): bool
    {
        try {
            // Tenter d'envoyer un email de test à l'administrateur
            $email = (new TemplatedEmail())
                ->from(new Address($this->adminEmail, 'EduPlay'))
                ->to(new Address($this->adminEmail, 'Admin'))
                ->subject('Test de configuration email')
                ->html('<p>Test de connexion SMTP réussi !</p>');

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Test SMTP échoué: ' . $e->getMessage());
            return false;
        }
    }
}