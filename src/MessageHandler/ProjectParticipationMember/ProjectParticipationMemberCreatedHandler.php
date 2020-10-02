<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\ProjectParticipationMember;

use InvalidArgumentException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\ProjectParticipationMember\ProjectParticipationMemberCreated;
use Unilend\Repository\ProjectParticipationMemberRepository;
use Unilend\Service\{ProjectParticipationMember\ProjectParticipationMemberNotifier};

class ProjectParticipationMemberCreatedHandler implements MessageHandlerInterface
{
    /** @var ProjectParticipationMemberRepository */
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;
    /** @var ProjectParticipationMemberNotifier */
    private ProjectParticipationMemberNotifier $projectParticipationMemberNotifier;

    /**
     * @param ProjectParticipationMemberRepository $projectParticipationMemberRepository
     * @param ProjectParticipationMemberNotifier   $projectParticipationMemberNotifier
     */
    public function __construct(
        ProjectParticipationMemberRepository $projectParticipationMemberRepository,
        ProjectParticipationMemberNotifier $projectParticipationMemberNotifier
    ) {
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
        $this->projectParticipationMemberNotifier   = $projectParticipationMemberNotifier;
    }

    /**
     * @param ProjectParticipationMemberCreated $projectParticipationMemberCreated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(ProjectParticipationMemberCreated $projectParticipationMemberCreated)
    {
        $projectParticipationMemberId = $projectParticipationMemberCreated->getProjectParticipationMemberId();
        $projectParticipationMember   = $this->projectParticipationMemberRepository->find($projectParticipationMemberId);

        if (!$projectParticipationMember) {
            throw new InvalidArgumentException(sprintf("The participationMember with id %d doesn't exist anymore", $projectParticipationMemberId));
        }

        $this->projectParticipationMemberNotifier->notifyMemberAdded($projectParticipationMember);
    }
}
