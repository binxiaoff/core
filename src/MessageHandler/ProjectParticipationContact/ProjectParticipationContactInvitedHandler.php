<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\ProjectParticipationContact;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\ProjectParticipationContact\ProjectParticipationContactInvited;
use Unilend\Repository\ProjectParticipationContactRepository;
use Unilend\Service\Client\ClientNotifier;

class ProjectParticipationContactInvitedHandler implements MessageHandlerInterface
{
    /** @var ClientNotifier */
    private $clientNotifier;
    /** @var ProjectParticipationContactRepository */
    private $projectParticipationContactRepository;

    /**
     * @param ClientNotifier                        $clientNotifier
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     */
    public function __construct(ClientNotifier $clientNotifier, ProjectParticipationContactRepository $projectParticipationContactRepository)
    {
        $this->clientNotifier                        = $clientNotifier;
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
    }

    /**
     * @param ProjectParticipationContactInvited $projectParticipationContactInvited
     *
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(ProjectParticipationContactInvited $projectParticipationContactInvited)
    {
        $projectParticipationContact = $this->projectParticipationContactRepository->findOneBy(['id' => $projectParticipationContactInvited->getProjectParticipationContactId()]);

        if ($projectParticipationContact) {
            $this->clientNotifier->notifyInvited(
                $projectParticipationContact->getAddedBy(),
                $projectParticipationContact->getClient(),
                $projectParticipationContact->getProjectParticipation()->getProject()
            );
        }
    }
}
