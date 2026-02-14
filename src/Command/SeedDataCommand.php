<?php

namespace App\Command;

use App\Entity\Course;
use App\Entity\Seance;
use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-data',
    description: 'Seed database with courses, seances, and subscriptions for testing',
)]
class SeedDataCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Seeding EduPlay Database');

        // Get users
        $teachers = $this->entityManager->getRepository(User::class)->findBy(['type' => 'enseignant']);
        $parents = $this->entityManager->getRepository(User::class)->findBy(['type' => 'parent']);
        $kids = $this->entityManager->getRepository(User::class)->findBy(['type' => 'enfant']);

        if (empty($teachers) || empty($parents) || empty($kids)) {
            $io->error('Please run "php bin/console app:seed-users" first to create users');
            return Command::FAILURE;
        }

        $io->section('Creating Courses');
        $courses = $this->createCourses($teachers, $io);

        $io->section('Creating Seances');
        $this->createSeances($courses, $io);

        $io->section('Creating Subscriptions');
        $this->createSubscriptions($courses, $parents, $kids, $io);

        $this->entityManager->flush();

        $io->success('Database seeded successfully!');
        $io->table(
            ['Entity', 'Count'],
            [
                ['Courses', count($courses)],
                ['Seances', $this->entityManager->getRepository(Seance::class)->count([])],
                ['Active Subscriptions', $this->entityManager->getRepository(Subscription::class)->count(['active' => true])],
            ]
        );

        return Command::SUCCESS;
    }

    private function createCourses(array $teachers, SymfonyStyle $io): array
    {
        $coursesData = [
            // Accepted courses (will have seances and subscriptions)
            ['title' => 'Python pour Débutants', 'description' => 'Apprendre les bases de la programmation Python avec des projets ludiques et interactifs.', 'level' => 'Beginner', 'duration' => 8, 'status' => 'accepted'],
            ['title' => 'Mathématiques Amusantes', 'description' => 'Découvrir les mathématiques à travers des jeux, des énigmes et des défis créatifs.', 'level' => 'Beginner', 'duration' => 10, 'status' => 'accepted'],
            ['title' => 'Robotique et Arduino', 'description' => 'Construire et programmer des robots avec Arduino. Parfait pour les enfants curieux de technologie.', 'level' => 'Intermediate', 'duration' => 12, 'status' => 'accepted'],
            ['title' => 'Sciences Naturelles', 'description' => 'Explorer le monde naturel: plantes, animaux, écosystèmes avec des expériences pratiques.', 'level' => 'Beginner', 'duration' => 6, 'status' => 'accepted'],
            ['title' => 'Développement Web HTML/CSS', 'description' => 'Créer des sites web magnifiques en apprenant HTML et CSS de manière progressive et amusante.', 'level' => 'Intermediate', 'duration' => 10, 'status' => 'accepted'],
            ['title' => 'Minecraft: Programmation avec Scratch', 'description' => 'Apprendre à programmer en créant des mods Minecraft avec Scratch et MakeCode.', 'level' => 'Beginner', 'duration' => 8, 'status' => 'accepted'],
            ['title' => 'Intelligence Artificielle pour Enfants', 'description' => 'Introduction ludique à l\'IA: reconnaissance d\'images, chatbots simples avec Teachable Machine.', 'level' => 'Advanced', 'duration' => 14, 'status' => 'accepted'],
            ['title' => 'Dessin et Animation 2D', 'description' => 'Créer des personnages et des animations 2D avec des outils numériques adaptés aux enfants.', 'level' => 'Intermediate', 'duration' => 10, 'status' => 'accepted'],
            ['title' => 'Électronique et Circuits', 'description' => 'Comprendre l\'électricité et créer des circuits simples avec LEDs, buzzers et capteurs.', 'level' => 'Intermediate', 'duration' => 8, 'status' => 'accepted'],
            ['title' => 'JavaScript Interactif', 'description' => 'Apprendre JavaScript en créant des jeux et des applications web interactives étape par étape.', 'level' => 'Advanced', 'duration' => 12, 'status' => 'accepted'],
            
            // Pending courses (waiting approval)
            ['title' => 'Modélisation 3D avec Blender', 'description' => 'Créer des objets 3D et des animations avec Blender, logiciel professionnel gratuit.', 'level' => 'Advanced', 'duration' => 16, 'status' => 'pending'],
            ['title' => 'Photographie Numérique', 'description' => 'Apprendre les bases de la photographie: composition, lumière et retouche photo simple.', 'level' => 'Beginner', 'duration' => 6, 'status' => 'pending'],
            ['title' => 'Cuisine et Chimie', 'description' => 'Explorer la science en cuisinant: réactions chimiques, états de la matière avec des recettes.', 'level' => 'Beginner', 'duration' => 8, 'status' => 'pending'],
            
            // Rejected courses
            ['title' => 'Hacking Éthique', 'description' => 'Introduction au hacking éthique et cybersécurité.', 'level' => 'Advanced', 'duration' => 20, 'status' => 'rejected'],
        ];

        $courses = [];
        $teacherIndex = 0;

        foreach ($coursesData as $data) {
            $course = new Course();
            $course->setTitle($data['title']);
            $course->setDescription($data['description']);
            $course->setLevel($data['level']);
            $course->setDurationTraining($data['duration']);
            $course->setStatus($data['status']);
            $course->setTeacherId($teachers[$teacherIndex % count($teachers)]);
            $course->setCreatedAt(new \DateTime('-' . rand(1, 60) . ' days'));

            $this->entityManager->persist($course);
            $courses[] = $course;

            $teacherIndex++;
            $io->writeln('✓ Created: ' . $course->getTitle() . ' (' . $course->getStatus() . ')');
        }

        return $courses;
    }

    private function createSeances(array $courses, SymfonyStyle $io): void
    {
        $locations = [
            'Salle A - Bâtiment Principal',
            'Laboratoire Informatique',
            'Salle Robotique',
            'Atelier Créatif',
            'Salle Multimédia',
            'Espace Sciences',
        ];

        $statuses = ['scheduled', 'ongoing', 'completed'];
        $seanceCount = 0;

        foreach ($courses as $course) {
            // Only create seances for accepted courses
            if ($course->getStatus() !== 'accepted') {
                continue;
            }

            // Create 3-5 seances per course
            $numSeances = rand(3, 5);

            for ($i = 0; $i < $numSeances; $i++) {
                $seance = new Seance();
                $seance->setTitle('Séance ' . ($i + 1) . ': ' . $course->getTitle());
                $seance->setCourse($course);
                
                // Create dates in the future and past
                $daysOffset = ($i * 7) - 14; // Start 2 weeks ago, weekly sessions
                $date = new \DateTime('+' . $daysOffset . ' days');
                $seance->setDate($date);
                
                // Set times (afternoon sessions)
                $startHour = 14 + rand(0, 2);
                $seance->setStartTime(new \DateTime(sprintf('%02d:00:00', $startHour)));
                $seance->setEndTime(new \DateTime(sprintf('%02d:30:00', $startHour + 1)));
                
                $seance->setLocation($locations[array_rand($locations)]);
                
                // Set status based on date
                if ($date < new \DateTime('-7 days')) {
                    $seance->setStatus('completed');
                } elseif ($date < new \DateTime('+1 day')) {
                    $seance->setStatus('ongoing');
                } else {
                    $seance->setStatus('scheduled');
                }
                
                $seance->setDescription('Séance pratique avec exercices et projets concrets.');

                $this->entityManager->persist($seance);
                $seanceCount++;
            }
        }

        $io->writeln("✓ Created {$seanceCount} seances");
    }

    private function createSubscriptions(array $courses, array $parents, array $kids, SymfonyStyle $io): void
    {
        $subscriptionCount = 0;
        $acceptedCourses = array_values(array_filter($courses, fn($c) => $c->getStatus() === 'accepted'));

        if (empty($acceptedCourses)) {
            $io->warning('No accepted courses found for subscriptions');
            return;
        }

        // Create varied subscription patterns
        foreach ($kids as $kid) {
            $parent = $kid->getParent();
            if (!$parent) {
                continue;
            }

            // Each kid subscribes to 2-4 random courses
            $numSubscriptions = rand(2, min(4, count($acceptedCourses)));
            
            // Shuffle and take first N courses
            $shuffled = $acceptedCourses;
            shuffle($shuffled);
            $selectedCourses = array_slice($shuffled, 0, $numSubscriptions);

            foreach ($selectedCourses as $course) {
                $subscription = new Subscription();
                $subscription->setParent($parent);
                $subscription->setKid($kid);
                $subscription->setCourse($course);
                $subscription->setActive(true);
                $subscription->setSubscribedAt(new \DateTime('-' . rand(1, 30) . ' days'));

                $this->entityManager->persist($subscription);
                $subscriptionCount++;
            }
        }

        $io->writeln("✓ Created {$subscriptionCount} subscriptions");
    }
}
