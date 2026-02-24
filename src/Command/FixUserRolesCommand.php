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
    name: 'app:fix-user-roles',
    description: 'Fix user roles based on their type',
)]
class FixUserRolesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Fixing User Roles');

        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findAll();

        $updated = 0;

        foreach ($users as $user) {
            $type = $user->getType();
            $expectedRole = match($type) {
                'admin' => 'ROLE_ADMIN',
                'enseignant' => 'ROLE_TEACHER',
                'parent' => 'ROLE_PARENT',
                'enfant' => 'ROLE_KID',
                default => 'ROLE_USER',
            };

            $currentRoles = $user->getRoles();
            
            // Check if user has the expected role
            if (!in_array($expectedRole, $currentRoles)) {
                $io->note(sprintf(
                    'Fixing roles for %s %s (type: %s)',
                    $user->getFirstName(),
                    $user->getLastName(),
                    $type
                ));
                
                $user->setRoles([$expectedRole]);
                $this->entityManager->persist($user);
                $updated++;
            }
        }

        if ($updated > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Fixed roles for %d users', $updated));
        } else {
            $io->success('All user roles are already correct');
        }

        // Display current users and their roles
        $io->section('Current Users:');
        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                $user->getEmail() ?? $user->getUsername(),
                $user->getType(),
                implode(', ', $user->getRoles()),
            ];
        }
        
        $io->table(['Email/Username', 'Type', 'Roles'], $rows);

        return Command::SUCCESS;
    }
}
