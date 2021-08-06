<?php

declare(strict_types=1);

namespace Unilend\Syndication\MessageHandler\ProjectParticipationMember;

use InvalidArgumentException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Syndication\Message\ProjectParticipationMember\ProjectParticipationMemberCreated;
use Unilend\Syndication\Repository\ProjectParticipationMemberRepository;
use Unilend\Syndication\Service\{ProjectParticipationMember\ProjectParticipationMemberNotifier};

class ProjectParticipationMemberCreatedHandler implements MessageHandlerInterface
{
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;

    private ProjectParticipationMemberNotifier $projectParticipationMemberNotifier;

    public function __construct(
        ProjectParticipationMemberRepository $projectParticipationMemberRepository,
        ProjectParticipationMemberNotifier $projectParticipationMemberNotifier
    ) {
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
        $this->projectParticipationMemberNotifier   = $projectParticipationMemberNotifier;
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(ProjectParticipationMemberCreated $projectParticipationMemberCreated)
    {
        $projectParticipationMemberId = $projectParticipationMemberCreated->getProjectParticipationMemberId();
        $projectParticipationMember   = $this->projectParticipationMemberRepository->find($projectParticipationMemberId);

        if (!$projectParticipationMember) {
            throw new InvalidArgumentException(\sprintf("The participationMember with id %d doesn't exist anymore", $projectParticipationMemberId));
        }

        $this->projectParticipationMemberNotifier->notifyMemberAdded($projectParticipationMember);
    }
}
