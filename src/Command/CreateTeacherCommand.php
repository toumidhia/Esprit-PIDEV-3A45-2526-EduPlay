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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'app:create-teacher',
    description: 'Create a new teacher user',
)]
class CreateTeacherCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Teacher email')
            ->addArgument('password', InputArgument::REQUIRED, 'Teacher password')
            ->addArgument('first_name', InputArgument::REQUIRED, 'Teacher first name')
            ->addArgument('last_name', InputArgument::REQUIRED, 'Teacher last name')
            ->addOption('specialite', 's', InputOption::VALUE_OPTIONAL, 'Teacher specialty')
            ->addOption('telephone', 't', InputOption::VALUE_OPTIONAL, 'Teacher phone number')
            ->addOption('adresse', 'a', InputOption::VALUE_OPTIONAL, 'Teacher address')
            ->setHelp('
This command allows you to create a teacher user.

Examples:
  <info>php bin/console app:create-teacher teacher@example.com password123 John Doe</info>
  <info>php bin/console app:create-teacher teacher@example.com password123 John Doe --specialite="Mathématiques" --telephone="0123456789"</info>
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $firstName = $input->getArgument('first_name');
        $lastName = $input->getArgument('last_name');
        $specialite = $input->getOption('specialite');
        $telephone = $input->getOption('telephone');
        $adresse = $input->getOption('adresse');

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error("User with email '{$email}' already exists!");
            return Command::FAILURE;
        }

        // Create new teacher user
        $teacher = new User();
        $teacher->setEmail($email);
        $teacher->setFirstName($firstName);
        $teacher->setLastName($lastName);
        $teacher->setType('enseignant');
        $teacher->setRoles(['ROLE_ENSEIGNANT']);

        // Set optional fields
        if ($specialite) {
            $teacher->setSpecialite($specialite);
        }

        if ($telephone) {
            $teacher->setTelephone($telephone);
        }

        if ($adresse) {
            $teacher->setAdresse($adresse);
        }

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($teacher, $password);
        $teacher->setPassword($hashedPassword);

        // Save to database
        $this->entityManager->persist($teacher);
        $this->entityManager->flush();

        $io->success([
            'Teacher account created successfully!',
            "Email: {$email}",
            "Name: {$firstName} {$lastName}",
            "Password: {$password}",
            "Role: ROLE_ENSEIGNANT",
            "Specialité: " . ($specialite ?: 'Not provided'),
            "Téléphone: " . ($telephone ?: 'Not provided'),
            "Adresse: " . ($adresse ?: 'Not provided'),
        ]);

        return Command::SUCCESS;
    }
}