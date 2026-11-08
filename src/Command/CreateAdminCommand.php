<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create a DURAN\'S Apparel administrator account'
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $io = new SymfonyStyle($input, $output);

        $io->title('DURAN\'S Apparel - Create Administrator');

        $fullname = trim($io->ask('Administrator full name', '') ?? '');
        $email = strtolower(trim($io->ask('Administrator email', '') ?? ''));

        if ($fullname === '' || $email === '') {
            $io->error('Full name and email are required.');
            return Command::FAILURE;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Please enter a valid email address.');
            return Command::FAILURE;
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            $io->error('An account with this email already exists.');
            return Command::FAILURE;
        }

        $password = $io->askHidden('Administrator password');
        $confirmPassword = $io->askHidden('Confirm administrator password');

        if (!$password || strlen($password) < 6) {
            $io->error('Password must be at least 6 characters.');
            return Command::FAILURE;
        }

        if ($password !== $confirmPassword) {
            $io->error('Passwords do not match.');
            return Command::FAILURE;
        }

        $admin = new User();

        $admin->setFullName($fullname);
        $admin->setEmail($email);

        // Only this protected command creates administrator accounts.
        $admin->setRoles(['ROLE_ADMIN']);

        // Hash the password before saving.
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, $password)
        );

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Administrator account created successfully!');

        return Command::SUCCESS;
    }
}