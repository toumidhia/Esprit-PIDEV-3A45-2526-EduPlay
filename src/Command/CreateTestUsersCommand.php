<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Creates test users for development (teacher, admin, parent, kid)',
)]
class CreateTestUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create Teacher
        $teacher = $this->createUser(
            'John',
            'Doe',
            new \DateTime('1985-05-15'),
            'teacher@eduplay.com',
            'password123',
            'teacher',
            ['ROLE_TEACHER'],
            true
        );

        // Create Admin
        $admin = $this->createUser(
            'Admin',
            'User',
            new \DateTime('1980-01-01'),
            'admin@eduplay.com',
            'password123',
            'admin',
            ['ROLE_ADMIN'],
            true
        );

        // Create Parent
        $parent = $this->createUser(
            'Jane',
            'Smith',
            new \DateTime('1990-08-20'),
            'parent@eduplay.com',
            'password123',
            'parent',
            ['ROLE_PARENT'],
            true
        );

        // Create Kid
        $kid = $this->createUser(
            'Tommy',
            'Smith',
            new \DateTime('2015-03-10'),
            'kid@eduplay.com',
            'password123',
            'kid',
            ['ROLE_KID'],
            true
        );

        try {
            $this->entityManager->persist($teacher);
            $this->entityManager->persist($admin);
            $this->entityManager->persist($parent);
            $this->entityManager->persist($kid);
            $this->entityManager->flush();

            $io->success('Test users created successfully!');
            $io->table(
                ['Role', 'Email', 'Password', 'ID'],
                [
                    ['Teacher', $teacher->getEmail(), 'password123', $teacher->getId()],
                    ['Admin', $admin->getEmail(), 'password123', $admin->getId()],
                    ['Parent', $parent->getEmail(), 'password123', $parent->getId()],
                    ['Kid', $kid->getEmail(), 'password123', $kid->getId()],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to create test users: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createUser(
        string $firstName,
        string $lastName,
        \DateTime $birthDate,
        string $email,
        string $password,
        string $type,
        array $role,
        bool $active
    ): User {
        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser) {
            return $existingUser;
        }

        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setBirthDate($birthDate);
        $user->setEmail($email);
        $user->setPassword($password);
        $user->setType($type);
        $user->setRole($role);
        $user->setActive($active);

        return $user;
    }
}
