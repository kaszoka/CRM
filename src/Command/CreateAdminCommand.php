<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Tworzy konto administratora CRM',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Adres e-mail administratora')
            ->addArgument('password', InputArgument::OPTIONAL, 'Hasło administratora')
            ->addArgument('firstName', InputArgument::OPTIONAL, 'Imię')
            ->addArgument('lastName', InputArgument::OPTIONAL, 'Nazwisko');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Tworzenie konta administratora CRM POLA');

        $helper = $this->getHelper('question');

        $email = $input->getArgument('email');
        if (!$email) {
            $question = new Question('Podaj adres e-mail: ');
            $email = $helper->ask($input, $output, $question);
        }

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error('Użytkownik z tym adresem e-mail już istnieje!');
            return Command::FAILURE;
        }

        $password = $input->getArgument('password');
        if (!$password) {
            $question = new Question('Podaj hasło (min. 8 znaków): ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $helper->ask($input, $output, $question);
        }

        if (strlen($password) < 8) {
            $io->error('Hasło musi mieć minimum 8 znaków!');
            return Command::FAILURE;
        }

        $firstName = $input->getArgument('firstName');
        if (!$firstName) {
            $question = new Question('Podaj imię: ');
            $firstName = $helper->ask($input, $output, $question);
        }

        $lastName = $input->getArgument('lastName');
        if (!$lastName) {
            $question = new Question('Podaj nazwisko: ');
            $lastName = $helper->ask($input, $output, $question);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles(['ROLE_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $io->error($error->getPropertyPath() . ': ' . $error->getMessage());
            }
            return Command::FAILURE;
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Administrator "%s" (%s) został utworzony pomyślnie!', $firstName . ' ' . $lastName, $email));

        return Command::SUCCESS;
    }
}

