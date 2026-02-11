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
    name: 'app:create-admin',
    description: 'Créer un administrateur',
)]
class CreateAdminCommand extends Command
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

        // Créer un admin
        $admin = new User();
        $admin->setFirstName('Super');
        $admin->setLastName('Admin');
        $admin->setEmail('admin@eduplay.com');
        $admin->setType('admin');
        
        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);

        // Sauvegarder
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Admin créé avec succès !');
        $io->table(
            ['Email', 'Mot de passe'],
            [['admin@eduplay.com', 'admin123']]
        );

        return Command::SUCCESS;
    }
}