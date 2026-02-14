<?php
// src/Command/TestEmailCommand.php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\EmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-email',
    description: 'Test l\'envoi d\'emails'
)]
class TestEmailCommand extends Command
{
    private $emailService;
    private $userRepository;

    public function __construct(EmailService $emailService, UserRepository $userRepository)
    {
        parent::__construct();
        $this->emailService = $emailService;
        $this->userRepository = $userRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Test de connexion
        $io->section('Test de connexion SMTP');
        if ($this->emailService->testConnection()) {
            $io->success('Connexion SMTP réussie !');
        } else {
            $io->error('Échec de la connexion SMTP');
            return Command::FAILURE;
        }

        // Afficher le nombre de parents
        $parentsCount = $this->userRepository->countParents();
        $io->info(sprintf('Nombre de parents dans la base: %d', $parentsCount));

        return Command::SUCCESS;
    }
}