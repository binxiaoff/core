<?php

declare(strict_types=1);

namespace Unilend\Agency\MessageHandler;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Agency\Entity\AbstractProjectMember;
use Unilend\Agency\Message\ProjectMemberUpdated;
use Unilend\Agency\Notifier\ProjectMemberNotifier;

class ProjectMemberUpdatedHandler implements MessageHandlerInterface
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
    public function __invoke(ProjectMemberUpdated $projectMemberUpdated)
    {
        if (false === \in_array('user', $projectMemberUpdated->getChangeSet(), true)) {
            return;
        }

        $manager = $this->registry->getManagerForClass($projectMemberUpdated->getProjectMemberClass());

        if (null === $manager) {
            throw new InvalidArgumentException(\sprintf('Missing manager for %s', $projectMemberUpdated->getProjectMemberClass()));
        }

        /** @var AbstractProjectMember $projectMember */
        $projectMember = $manager->find($projectMemberUpdated->getProjectMemberClass(), $projectMemberUpdated->getProjectMemberId());

        if (null === $projectMember) {
            throw new InvalidArgumentException(
                \sprintf("Project member of class %s with id %d doesn't exist", $projectMemberUpdated->getProjectMemberClass(), $projectMemberUpdated->getProjectMemberId())
            );
        }

        if ($projectMember && $projectMember->getProject()->isPublished()) {
            $this->projectMemberNotifier->notifyProjectPublication($projectMember);
        }
    }
}
