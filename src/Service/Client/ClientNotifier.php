<?php

declare(strict_types=1);

namespace Unilend\Service\Client;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Swift_RfcComplianceException;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Entity\{Clients, ClientsStatus, ProjectParticipation, TemporaryLinksLogin};
use Unilend\Repository\TemporaryLinksLoginRepository;
use Unilend\SwiftMailer\{TemplateMessageProvider, UnilendMailer};

class ClientNotifier
{
    /** @var TemporaryLinksLoginRepository */
    private $temporaryLinksLoginRepository;
    /** @var RouterInterface */
    private $router;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var UnilendMailer */
    private $mailer;

    /**
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     * @param RouterInterface               $router
     * @param TemplateMessageProvider       $messageProvider
     * @param UnilendMailer                 $mailer
     */
    public function __construct(
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository,
        RouterInterface $router,
        TemplateMessageProvider $messageProvider,
        UnilendMailer $mailer
    ) {
        $this->temporaryLinksLoginRepository = $temporaryLinksLoginRepository;
        $this->router                        = $router;
        $this->messageProvider               = $messageProvider;
        $this->mailer                        = $mailer;
    }

    /**
     * @param Clients              $inviter
     * @param Clients              $invitee
     * @param ProjectParticipation $projectParticipation
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function notifyInvited(Clients $inviter, Clients $invitee, ProjectParticipation $projectParticipation): int
    {
        $sent = 0;
        if (ClientsStatus::STATUS_INVITED === $invitee->getCurrentStatus()->getStatus()) {
            $sent += $this->notifyNewClientInvited($inviter, $invitee, $projectParticipation);
        } else {
            $sent += $this->notifyInvitedToProject($inviter, $invitee, $projectParticipation);
        }

        return $sent;
    }

    /**
     * @param Clients              $inviter
     * @param Clients              $invitee
     * @param ProjectParticipation $projectParticipation
     *
     * @throws Exception
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function notifyNewClientInvited(Clients $inviter, Clients $invitee, ProjectParticipation $projectParticipation): int
    {
        $token   = $this->temporaryLinksLoginRepository->findOneBy(['idClient' => $invitee]);
        $project = $projectParticipation->getProject();

        if ($token) {
            $token->extendLongExpiration();
            $this->temporaryLinksLoginRepository->save($token);
        } else {
            $token = $this->temporaryLinksLoginRepository->generateTemporaryLink($invitee, TemporaryLinksLogin::PASSWORD_TOKEN_LIFETIME_LONG);
        }

        $keywords = [
            'inviterName'    => $inviter->getLastName() . ' ' . $inviter->getFirstName(),
            'project'        => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
            'initAccountUrl' => $this->router->generate('project_invitation', [
                'securityToken'          => $token->getToken(),
                'projectParticipationId' => $projectParticipation->getId(),
            ], RouterInterface::ABSOLUTE_URL),
        ];

        $message = $this->messageProvider->newMessage('project-invitation-new-user', $keywords);
        $message->setTo($invitee->getEmail());

        return $this->mailer->send($message);
    }

    /**
     * @param Clients              $inviter
     * @param Clients              $invitee
     * @param ProjectParticipation $projectParticipation
     *
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function notifyInvitedToProject(Clients $inviter, Clients $invitee, ProjectParticipation $projectParticipation): int
    {
        $project     = $projectParticipation->getProject();
        $projectUrl  = $this->router->generate('lender_project_details', ['slug' => $project->getSlug()], RouterInterface::ABSOLUTE_URL);
        $projectName = $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle();

        $keywords = [
            'inviterName' => $inviter->getLastName() . ' ' . $inviter->getFirstName(),
            'firstName'   => $invitee->getFirstName(),
            'projectUrl'  => $projectUrl,
            'projectName' => $projectName,
        ];
        $message = $this->messageProvider->newMessage('project-publication', $keywords);
        $message->setTo($invitee->getEmail());

        return $this->mailer->send($message);
    }
}
