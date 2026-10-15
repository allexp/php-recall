<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Создаёт администратора или безопасно заменяет его пароль. */
#[AsCommand(
    name: 'app:admin:create',
    description: 'Создаёт администратора или обновляет его пароль',
)]
final class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::OPTIONAL, 'Email администратора');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = trim((string) ($input->getArgument('email') ?: $io->ask('Email администратора')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Укажите корректный email администратора.');

            return Command::INVALID;
        }

        $password = (string) $io->askHidden('Пароль администратора');
        if (mb_strlen($password) < 12) {
            $io->error('Пароль должен содержать не менее 12 символов.');

            return Command::INVALID;
        }

        $normalizedEmail = mb_strtolower($email);
        $admin = $this->entityManager->getRepository(AdminUser::class)->findOneBy([
            'email' => $normalizedEmail,
        ]);

        if (!$admin instanceof AdminUser) {
            $admin = (new AdminUser())->setEmail($normalizedEmail);
            $this->entityManager->persist($admin);
        }

        $admin->setPassword($this->passwordHasher->hashPassword($admin, $password));
        $this->entityManager->flush();

        $io->success(sprintf('Администратор %s сохранён.', $admin->getEmail()));

        return Command::SUCCESS;
    }
}
