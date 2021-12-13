<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Command;

use JsonException;
use KLS\Core\Entity\User;
use KLS\Core\Repository\UserRepository;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Notifier\ProjectMemberNotifier;
use KLS\Syndication\Agency\Repository\ProjectRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendNotificationsFromProject extends Command
{
    protected static $defaultName = 'kls:agency:project_publication:notify';

    private ProjectMemberNotifier $projectMemberNotifier;
    private ProjectRepository $projectRepository;
    private UserRepository $userRepository;

    public function __construct(
        ProjectMemberNotifier $projectMemberNotifier,
        ProjectRepository $projectRepository,
        UserRepository $userRepository
    ) {
        parent::__construct();

        $this->projectMemberNotifier = $projectMemberNotifier;
        $this->projectRepository     = $projectRepository;
        $this->userRepository        = $userRepository;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send notifications from an agency project publication')
            ->addOption('project', null, InputOption::VALUE_REQUIRED, 'Project id')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'User id (member of the project)')
        ;
    }

    /**
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projectId = $input->getOption('project');
        $userId    = $input->getOption('user');

        $project = $this->projectRepository->find($projectId);
        $user    = $this->userRepository->find($userId);

        if (false === $project instanceof Project) {
            $io->error(\sprintf('Agency project %s not found', $projectId));
        }

        if (false === $user instanceof User) {
            $io->error(\sprintf('User %s not found', $user));
        }

        foreach ($project->getMembers() as $projectMember) {
            if ($projectMember->getUser() === $user) {
                $this->projectMemberNotifier->notifyProjectPublication($projectMember);
            }
        }

        return self::SUCCESS;
    }
}
