<?php

declare(strict_types=1);

namespace KLS\Core\Command;

use Exception;
use KLS\Core\Entity\User;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Service\Staff\StaffNotifier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InviteUserCommand extends Command
{
    protected static $defaultName = 'kls:core:user:invite';

    private UserRepository $userRepository;
    private StaffNotifier $staffNotifier;

    public function __construct(UserRepository $userRepository, StaffNotifier $staffNotifier)
    {
        parent::__construct();

        $this->userRepository = $userRepository;
        $this->staffNotifier  = $staffNotifier;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('This command notify a list of users to initialise their accounts.')
            ->addArgument(
                'users',
                InputArgument::IS_ARRAY,
                'Which users do you want to sign (separate multiple id with a space)?'
            )
        ;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userIds = $input->getArgument('users');

        foreach ($userIds as $userId) {
            $user  = $this->userRepository->find($userId);
            $staff = $user instanceof User ? $user->getStaff() : [];
            if (0 === \count($staff)) {
                continue;
            }
            $currentStaff = $staff->current();
            while ($currentStaff && 1 > $this->staffNotifier->notifyUserInitialisation($currentStaff)) {
                $currentStaff = $staff->next();
            }
        }

        return Command::SUCCESS;
    }
}
