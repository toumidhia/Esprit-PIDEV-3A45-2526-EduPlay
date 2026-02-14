<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates an admin user',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $output->writeln([
            'Admin User Creator',
            '==================',
            '',
        ]);

        $question = new Question('Enter email: ');
        $email = $helper->ask($input, $output, $question);

        $question = new Question('Enter first name: ');
        $firstName = $helper->ask($input, $output, $question);

        $question = new Question('Enter last name: ');
        $lastName = $helper->ask($input, $output, $question);

        $question = new Question('Enter password: ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $question);

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $output->writeln('<error>User with this email already exists!</error>');
            return Command::FAILURE;
        }

        // Create new user
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setType('admin');
        $user->setActive(true);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Set roles
        $user->setRoles(['ROLE_ADMIN']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Admin user created successfully!</info>');
        $output->writeln(sprintf('Email: %s', $email));
        $output->writeln(sprintf('Name: %s %s', $firstName, $lastName));

        return Command::SUCCESS;
    }
}