<?php

declare(strict_types=1);

namespace Unilend\Core\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\UserRepository;
use Unilend\Core\Service\Staff\StaffNotifier;

class InviteUserCommand extends Command
{
    protected static $defaultName = 'kls:user:invite';

    /** @var UserRepository */
    private $userRepository;
    /** @var StaffNotifier */
    private $staffNotifier;

    public function __construct(UserRepository $userRepository, StaffNotifier $staffNotifier)
    {
        parent::__construct();

        $this->userRepository = $userRepository;
        $this->staffNotifier  = $staffNotifier;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('This command notify a list of users to initialise their accounts.');
        $this->addArgument('users', InputArgument::IS_ARRAY, 'Which users do you want to sign (separate multiple id with a space)?');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
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

        return 0;
    }
}
