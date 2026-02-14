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
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(
    name: 'app:create-parent',
    description: 'Create a new parent user',
)]
class CreateParentCommand extends Command
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
            ->addArgument('email', InputArgument::OPTIONAL, 'Parent email')
            ->addArgument('password', InputArgument::OPTIONAL, 'Parent password')
            ->addArgument('first_name', InputArgument::OPTIONAL, 'Parent first name')
            ->addArgument('last_name', InputArgument::OPTIONAL, 'Parent last name')
            ->addOption('interactive', 'i', InputOption::VALUE_NONE, 'Interactive mode')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List all parents')
            ->addOption('delete', 'd', InputOption::VALUE_REQUIRED, 'Delete parent by email')
            ->setHelp('
This command allows you to create a parent user.

Examples:
  <info>php bin/console app:create-parent</info> (interactive mode)
  <info>php bin/console app:create-parent parent@example.com password123 John Doe</info>
  <info>php bin/console app:create-parent --list</info> (list all parents)
  <info>php bin/console app:create-parent --delete="parent@example.com"</info>
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Handle list option
        if ($input->getOption('list')) {
            return $this->listParents($io);
        }

        // Handle delete option
        if ($deleteEmail = $input->getOption('delete')) {
            return $this->deleteParent($deleteEmail, $io);
        }

        // Check if interactive mode or arguments provided
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $firstName = $input->getArgument('first_name');
        $lastName = $input->getArgument('last_name');

        // If no arguments and not interactive mode, show help
        if (!$email && !$input->getOption('interactive')) {
            $io->note('No arguments provided. Using interactive mode...');
            return $this->interactiveMode($io);
        }

        // If email provided but other arguments missing, ask for them
        if ($email && (!$password || !$firstName || !$lastName)) {
            return $this->interactiveMode($io, $email, $password, $firstName, $lastName);
        }

        // Create parent with provided arguments
        return $this->createParent($email, $password, $firstName, $lastName, $io);
    }

    private function interactiveMode(
        SymfonyStyle $io,
        ?string $email = null,
        ?string $password = null,
        ?string $firstName = null,
        ?string $lastName = null
    ): int {
        $io->title('Create New Parent Account');

        // Ask for email if not provided
        if (!$email) {
            $email = $io->ask('Email address', null, function ($value) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Please enter a valid email address.');
                }

                // Check if email already exists
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $value]);
                if ($existingUser) {
                    throw new \RuntimeException('This email is already registered.');
                }

                return $value;
            });
        }

        // Ask for first name if not provided
        if (!$firstName) {
            $firstName = $io->ask('First name', null, function ($value) {
                if (empty($value)) {
                    throw new \RuntimeException('First name cannot be empty.');
                }
                return $value;
            });
        }

        // Ask for last name if not provided
        if (!$lastName) {
            $lastName = $io->ask('Last name', null, function ($value) {
                if (empty($value)) {
                    throw new \RuntimeException('Last name cannot be empty.');
                }
                return $value;
            });
        }

        // Ask for password if not provided
        if (!$password) {
            $password = $io->askHidden('Password (min 6 characters)', function ($value) {
                if (strlen($value) < 6) {
                    throw new \RuntimeException('Password must be at least 6 characters long.');
                }
                return $value;
            });

            $confirmPassword = $io->askHidden('Confirm password', function ($value) use ($password) {
                if ($value !== $password) {
                    throw new \RuntimeException('Passwords do not match.');
                }
                return $value;
            });
        }

        // Ask for phone (optional)
        $phone = $io->ask('Phone number (optional)', null);

        // Ask for address (optional)
        $address = $io->ask('Address (optional)', null);

        // Confirm creation
        if (!$io->confirm("Create parent account for {$firstName} {$lastName} ({$email})?", true)) {
            $io->warning('Operation cancelled.');
            return Command::SUCCESS;
        }

        return $this->createParent($email, $password, $firstName, $lastName, $io, $phone, $address);
    }

    private function createParent(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        SymfonyStyle $io,
        ?string $phone = null,
        ?string $address = null
    ): int {
        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error("User with email '{$email}' already exists!");
            return Command::FAILURE;
        }

        // Create new parent user
        $parent = new User();
        $parent->setEmail($email);
        $parent->setFirstName($firstName);
        $parent->setLastName($lastName);

        // Use telephone (not phone) - check if method exists
        if (method_exists($parent, 'setTelephone') && $phone) {
            $parent->setTelephone($phone);
        } elseif ($phone) {
            // If method doesn't exist, try setPhone as fallback
            if (method_exists($parent, 'setPhone')) {
                $parent->setPhone($phone);
            }
        }

        // Use adresse (not address) - check if method exists
        if (method_exists($parent, 'setAdresse') && $address) {
            $parent->setAdresse($address);
        } elseif ($address) {
            // If method doesn't exist, try setAddress as fallback
            if (method_exists($parent, 'setAddress')) {
                $parent->setAddress($address);
            }
        }

        $parent->setType('parent');
        $parent->setRoles(['ROLE_PARENT']);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($parent, $password);
        $parent->setPassword($hashedPassword);

        // Save to database
        $this->entityManager->persist($parent);
        $this->entityManager->flush();

        $io->success([
            'Parent account created successfully!',
            "Email: {$email}",
            "Name: {$firstName} {$lastName}",
            "Password: {$password}",
            "Role: ROLE_PARENT",
            "Telephone: " . ($phone ?: 'Not provided'),
            "Adresse: " . ($address ?: 'Not provided'),
        ]);

        $io->note('Please provide these credentials to the parent.');

        return Command::SUCCESS;
    }

    private function listParents(SymfonyStyle $io): int
    {
        $parentRepository = $this->entityManager->getRepository(User::class);
        $parents = $parentRepository->findBy(['type' => 'parent'], ['lastName' => 'ASC', 'firstName' => 'ASC']);

        if (empty($parents)) {
            $io->warning('No parent accounts found.');
            return Command::SUCCESS;
        }

        $io->title('Parent Accounts');

        $table = new Table($io);
        $table->setHeaders(['ID', 'Email', 'Name', 'Telephone', 'Adresse', 'Created']);

        foreach ($parents as $parent) {
            // Use getTelephone() or getPhone() depending on what exists
            $telephone = '';
            if (method_exists($parent, 'getTelephone')) {
                $telephone = $parent->getTelephone();
            } elseif (method_exists($parent, 'getPhone')) {
                $telephone = $parent->getPhone();
            }

            // Use getAdresse() or getAddress() depending on what exists
            $adresse = '';
            if (method_exists($parent, 'getAdresse')) {
                $adresse = $parent->getAdresse();
            } elseif (method_exists($parent, 'getAddress')) {
                $adresse = $parent->getAddress();
            }

            $table->addRow([
                $parent->getId(),
                $parent->getEmail(),
                $parent->getFirstName() . ' ' . $parent->getLastName(),
                $telephone ?: 'N/A',
                $adresse ? (strlen($adresse) > 20 ? substr($adresse, 0, 20) . '...' : $adresse) : 'N/A',
                $parent->getCreatedAt() ? $parent->getCreatedAt()->format('Y-m-d') : 'N/A',
            ]);
        }

        $table->render();

        $io->text(sprintf('Total parents: %d', count($parents)));

        return Command::SUCCESS;
    }

    private function deleteParent(string $email, SymfonyStyle $io): int
    {
        $parentRepository = $this->entityManager->getRepository(User::class);
        $parent = $parentRepository->findOneBy(['email' => $email, 'type' => 'parent']);

        if (!$parent) {
            $io->error("Parent with email '{$email}' not found.");
            return Command::FAILURE;
        }

        // Check if parent has active subscriptions
        // You might want to add this check if you have a Subscription entity
        // $subscriptionCount = $this->entityManager->getRepository(Subscription::class)->count(['parent' => $parent]);
        // if ($subscriptionCount > 0) {
        //     $io->error("Cannot delete parent with active subscriptions.");
        //     return Command::FAILURE;
        // }

        if (!$io->confirm("Are you sure you want to delete parent '{$parent->getFirstName()} {$parent->getLastName()}' ({$email})?", false)) {
            $io->warning('Deletion cancelled.');
            return Command::SUCCESS;
        }

        $this->entityManager->remove($parent);
        $this->entityManager->flush();

        $io->success("Parent '{$email}' deleted successfully.");

        return Command::SUCCESS;
    }
}