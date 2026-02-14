<?php
// src/Controller/TestEmailController.php

namespace App\Controller;

use App\Repository\UserRepository;  // ← IMPORTANT: C'est App\Repository, PAS App\Controller
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use App\Service\EmailService;
use App\Entity\Course;
class TestEmailController extends AbstractController
{
    #[Route('/test-email', name: 'app_test_email')]
    public function testEmail(MailerInterface $mailer): Response
    {
        try {
            $email = (new TemplatedEmail())
                ->from('saadliwassieo@gmail.com')
                ->to('saadliwassieo@gmail.com')
                ->subject('✅ Test de configuration Gmail - EduPlay')
                ->html('
                    <h1 style="color: #4CAF50;">Test réussi !</h1>
                    <p>Félicitations ! La configuration email fonctionne correctement.</p>
                    <p>Vous pouvez maintenant créer des cours et les parents recevront les notifications.</p>
                    <hr>
                    <p style="color: #666;">Email envoyé depuis EduPlay</p>
                ');

            $mailer->send($email);

            return new Response('
                <html>
                    <head><title>Test Email</title></head>
                    <body style="font-family: Arial; padding: 20px;">
                        <div style="background: #4CAF50; color: white; padding: 20px; border-radius: 5px;">
                            <h1>✅ Succès !</h1>
                            <p>Email de test envoyé à saadliwassieo@gmail.com</p>
                        </div>
                        <p>Vérifiez votre boîte Gmail (et les dossiers Spam/Promotions).</p>
                        <p><a href="/course/new">Retour à la création de cours</a></p>
                    </body>
                </html>
            ');
        } catch (\Exception $e) {
            return new Response('
                <html>
                    <head><title>Test Email</title></head>
                    <body style="font-family: Arial; padding: 20px;">
                        <div style="background: #f44336; color: white; padding: 20px; border-radius: 5px;">
                            <h1>❌ Erreur</h1>
                            <p>' . $e->getMessage() . '</p>
                        </div>
                        <p>Vérifiez votre configuration dans le fichier .env</p>
                    </body>
                </html>
            ');
        }
    }

    #[Route('/test-parents', name: 'app_test_parents')]
    public function testParents(UserRepository $userRepository): Response  // ← Maintenant corrigé
    {
        $parents = $userRepository->findAllParents();

        $html = '<h1>Liste des parents</h1>';
        $html .= '<p>Nombre de parents trouvés: ' . count($parents) . '</p>';
        $html .= '<p>Email recherché: saadliwassieo@gmail.com</p>';

        if (count($parents) > 0) {
            $html .= '<ul>';
            $found = false;
            foreach ($parents as $parent) {
                $isTarget = ($parent->getEmail() == 'saadliwassieo@gmail.com');
                if ($isTarget) $found = true;

                $html .= sprintf(
                    '<li style="%s">
                        <strong>%s %s</strong><br>
                        Email: %s %s<br>
                        Rôles: %s
                    </li>',
                    $isTarget ? 'background-color: #e8f5e8; padding: 10px; border: 2px solid green;' : '',
                    $parent->getFirstName(),
                    $parent->getLastName(),
                    $parent->getEmail(),
                    $isTarget ? ' ✅ (VOTRE EMAIL)' : '',
                    implode(', ', $parent->getRoles())
                );
            }
            $html .= '</ul>';

            if (!$found) {
                $html .= '<p style="color: red; font-weight: bold;">⚠️ Votre email saadliwassieo@gmail.com n\'est PAS dans la liste des parents !</p>';
            }
        } else {
            $html .= '<p style="color:red; font-weight: bold;">❌ Aucun parent trouvé avec le rôle ROLE_PARENT</p>';
        }

        // Vérification SQL directe
        $html .= '<h2>Vérification dans la base de données:</h2>';
        $html .= '<p>Exécutez cette requête SQL dans phpMyAdmin:</p>';
        $html .= '<pre style="background: #f4f4f4; padding: 10px;">SELECT id, email, first_name, last_name, roles FROM user WHERE email = \'saadliwassieo@gmail.com\';</pre>';

        return new Response($html);
    }

    #[Route('/view-logs', name: 'app_view_logs')]
    public function viewLogs(): Response
    {
        $logFile = __DIR__ . '/../../var/log/dev.log';

        if (!file_exists($logFile)) {
            return new Response('Fichier de log non trouvé');
        }

        $logs = file_get_contents($logFile);
        $lastLines = implode("\n", array_slice(explode("\n", $logs), -50)); // Dernières 50 lignes

        return new Response('<pre>' . htmlspecialchars($lastLines) . '</pre>');
    }

    #[Route('/test-email-service', name: 'app_test_email_service')]
    public function testEmailService(EmailService $emailService, UserRepository $userRepository): Response
    {
        try {
            // Récupérer tous les parents
            $parents = $userRepository->findAllParents();

            // Créer un cours factice pour le test
            $course = new Course();
            $course->setTitle('Test Course')
                ->setDescription('Test Description')
                ->setDurationTraining('8 semaines')
                ->setLevel('Beginner');

            // Tester l'envoi
            $emailService->sendNewCourseNotification($course, $parents);

            return new Response('✅ Test du service email effectué. Vérifiez les logs.');
        } catch (\Exception $e) {
            return new Response('❌ Erreur: ' . $e->getMessage());
        }
    }
}