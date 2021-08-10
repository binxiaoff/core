<?php

declare(strict_types=1);

namespace KLS\Agency\MessageHandler;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use JsonException;
use KLS\Agency\Entity\AbstractProjectMember;
use KLS\Agency\Message\ProjectMemberCreated;
use KLS\Agency\Notifier\ProjectMemberNotifier;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ProjectMemberCreatedHandler implements MessageHandlerInterface
{
    private ManagerRegistry       $registry;
    private ProjectMemberNotifier $projectMemberNotifier;

    public function __construct(ManagerRegistry $registry, ProjectMemberNotifier $projectMemberNotifier)
    {
        $this->registry              = $registry;
        $this->projectMemberNotifier = $projectMemberNotifier;
    }

    /**
     * @throws JsonException
     */
    public function __invoke(ProjectMemberCreated $projectMemberCreated)
    {
        $manager = $this->registry->getManagerForClass($projectMemberCreated->getProjectMemberClass());

        if (null === $manager) {
            throw new InvalidArgumentException(\sprintf('Missing manager for %s', $projectMemberCreated->getProjectMemberClass()));
        }

        /** @var AbstractProjectMember $projectMember */
        $projectMember = $manager->find($projectMemberCreated->getProjectMemberClass(), $projectMemberCreated->getProjectMemberId());

        if (null === $projectMember) {
            throw new InvalidArgumentException(
                \sprintf("Project member of class %s with id %d doesn't exist", $projectMemberCreated->getProjectMemberClass(), $projectMemberCreated->getProjectMemberId())
            );
        }

        if ($projectMember && $projectMember->getProject()->isPublished()) {
            $this->projectMemberNotifier->notifyProjectPublication($projectMember);
        }
    }
}
