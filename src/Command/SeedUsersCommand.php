<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed-users',
    description: 'Seed sample users including parents, enfants, enseignants and admins',
)]
class SeedUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if users already exist
        $existingUsers = $this->entityManager->getRepository(User::class)->count([]);
        
        if ($existingUsers > 0) {
            $io->warning('Users already exist in the database.');
            if (!$io->confirm('Do you want to add more sample users anyway?', false)) {
                return Command::SUCCESS;
            }
        }

        $io->section('Creating sample users...');

        // Create Admin users
        $admin1 = $this->createUser(
            'Jean',
            'Admin',
            'admin@eduplay.com',
            null,
            'admin123',
            'admin',
            new \DateTime('1985-03-15')
        );
        $this->entityManager->persist($admin1);

        $admin2 = $this->createUser(
            'Marie',
            'Administrateur',
            'marie.admin@eduplay.com',
            null,
            'admin123',
            'admin',
            new \DateTime('1988-07-20')
        );
        $this->entityManager->persist($admin2);

        // Create Enseignants (Teachers)
        $enseignant1 = $this->createUser(
            'Sophie',
            'Martin',
            'sophie.martin@eduplay.com',
            null,
            'teacher123',
            'enseignant',
            new \DateTime('1980-05-10'),
            '0612345678',
            null,
            'Mathématiques'
        );
        $this->entityManager->persist($enseignant1);

        $enseignant2 = $this->createUser(
            'Pierre',
            'Dubois',
            'pierre.dubois@eduplay.com',
            null,
            'teacher123',
            'enseignant',
            new \DateTime('1975-11-22'),
            '0623456789',
            null,
            'Français'
        );
        $this->entityManager->persist($enseignant2);

        $enseignant3 = $this->createUser(
            'Claire',
            'Bernard',
            'claire.bernard@eduplay.com',
            null,
            'teacher123',
            'enseignant',
            new \DateTime('1990-02-14'),
            '0634567890',
            null,
            'Sciences'
        );
        $this->entityManager->persist($enseignant3);

        // Create Parent users
        $parent1 = $this->createUser(
            'Laurent',
            'Dupont',
            'laurent.dupont@email.com',
            null,
            'parent123',
            'parent',
            new \DateTime('1982-09-12'),
            '0645678901',
            '15 Rue de la République, Paris 75001'
        );
        $this->entityManager->persist($parent1);

        $parent2 = $this->createUser(
            'Isabelle',
            'Leroy',
            'isabelle.leroy@email.com',
            null,
            'parent123',
            'parent',
            new \DateTime('1985-04-30'),
            '0656789012',
            '8 Avenue des Champs, Lyon 69001'
        );
        $this->entityManager->persist($parent2);

        $parent3 = $this->createUser(
            'Thomas',
            'Moreau',
            'thomas.moreau@email.com',
            null,
            'parent123',
            'parent',
            new \DateTime('1979-12-08'),
            '0667890123',
            '22 Boulevard Victor Hugo, Marseille 13001'
        );
        $this->entityManager->persist($parent3);

        // Flush parents first to get their IDs
        $this->entityManager->flush();

        // Create Enfant users linked to parents
        $enfant1 = $this->createUser(
            'Lucas',
            'Dupont',
            null,
            'lucas.dupont',
            'enfant123',
            'enfant',
            new \DateTime('2015-06-15'),
            null,
            null,
            null,
            'CE2'
        );
        $enfant1->setParent($parent1);
        $this->entityManager->persist($enfant1);

        $enfant2 = $this->createUser(
            'Emma',
            'Dupont',
            null,
            'emma.dupont',
            'enfant123',
            'enfant',
            new \DateTime('2017-03-20'),
            null,
            null,
            null,
            'CP'
        );
        $enfant2->setParent($parent1);
        $this->entityManager->persist($enfant2);

        $enfant3 = $this->createUser(
            'Léa',
            'Leroy',
            null,
            'lea.leroy',
            'enfant123',
            'enfant',
            new \DateTime('2016-08-10'),
            null,
            null,
            null,
            'CE1'
        );
        $enfant3->setParent($parent2);
        $this->entityManager->persist($enfant3);

        $enfant4 = $this->createUser(
            'Hugo',
            'Leroy',
            null,
            'hugo.leroy',
            'enfant123',
            'enfant',
            new \DateTime('2014-11-25'),
            null,
            null,
            null,
            'CM1'
        );
        $enfant4->setParent($parent2);
        $this->entityManager->persist($enfant4);

        $enfant5 = $this->createUser(
            'Chloé',
            'Moreau',
            null,
            'chloe.moreau',
            'enfant123',
            'enfant',
            new \DateTime('2015-01-18'),
            null,
            null,
            null,
            'CE2'
        );
        $enfant5->setParent($parent3);
        $this->entityManager->persist($enfant5);

        $enfant6 = $this->createUser(
            'Nathan',
            'Moreau',
            null,
            'nathan.moreau',
            'enfant123',
            'enfant',
            new \DateTime('2018-05-05'),
            null,
            null,
            null,
            'GS'
        );
        $enfant6->setParent($parent3);
        $this->entityManager->persist($enfant6);

        // Final flush
        $this->entityManager->flush();

        $io->success('Sample users have been successfully created!');
        
        $io->section('Summary:');
        $io->table(
            ['Type', 'Count', 'Sample Credentials'],
            [
                ['Admin', '2', 'admin@eduplay.com / admin123'],
                ['Enseignant', '3', 'sophie.martin@eduplay.com / teacher123'],
                ['Parent', '3', 'laurent.dupont@email.com / parent123'],
                ['Enfant', '6', 'lucas.dupont / enfant123'],
            ]
        );

        $io->note('All passwords are hashed. Use the credentials shown above to login.');

        return Command::SUCCESS;
    }

    private function createUser(
        string $firstName,
        string $lastName,
        ?string $email,
        ?string $username,
        string $plainPassword,
        string $type,
        \DateTime $birthDate,
        ?string $telephone = null,
        ?string $adresse = null,
        ?string $specialite = null,
        ?string $niveau = null
    ): User {
        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setType($type); // This automatically sets the roles
        $user->setBirthDate($birthDate);
        $user->setActive(true);
        
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        // Set optional fields
        if ($telephone) {
            $user->setTelephone($telephone);
        }
        if ($adresse) {
            $user->setAdresse($adresse);
        }
        if ($specialite) {
            $user->setSpecialite($specialite);
        }
        if ($niveau) {
            $user->setNiveau($niveau);
        }

        return $user;
    }
}
