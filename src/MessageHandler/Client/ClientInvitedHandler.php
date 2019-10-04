<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Swift_RfcComplianceException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Message\Client\ClientInvited;
use Unilend\Repository\ProjectParticipationContactRepository;
use Unilend\Service\Client\ClientNotifier;

class ClientInvitedHandler implements MessageHandlerInterface
{
    /** @var ClientNotifier */
    private $clientNotifier;
    /** @var ProjectParticipationContactRepository */
    private $projectParticipationContactRepository;

    /**
     * @param ClientNotifier                        $clientNotifier
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     */
    public function __construct(
        ClientNotifier $clientNotifier,
        ProjectParticipationContactRepository $projectParticipationContactRepository
    ) {
        $this->clientNotifier                        = $clientNotifier;
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
    }

    /**
     * @param ClientInvited $clientInvited
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Swift_RfcComplianceException
     */
    public function __invoke(ClientInvited $clientInvited)
    {
        $projectParticipationContact = $this->projectParticipationContactRepository->findOneBy(['id' => $clientInvited->getProjectParticipationContactId()]);

        if ($projectParticipationContact) {
            $this->clientNotifier->notifyInvited(
                $projectParticipationContact->getAddedBy(),
                $projectParticipationContact->getClient(),
                $projectParticipationContact->getProjectParticipation()->getProject()
            );
        }
    }
}
