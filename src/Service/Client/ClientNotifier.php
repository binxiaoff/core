<?php

declare(strict_types=1);

namespace Unilend\Service\Client;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Swift_RfcComplianceException;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Entity\{Clients, ClientsStatus, Project, ProjectStatus, TemporaryLinksLogin};
use Unilend\Repository\TemporaryLinksLoginRepository;
use Unilend\Service\NotificationManager;
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
    /** @var NotificationManager */
    private $notificationManager;

    /**
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     * @param RouterInterface               $router
     * @param TemplateMessageProvider       $messageProvider
     * @param UnilendMailer                 $mailer
     * @param NotificationManager           $notificationManager
     */
    public function __construct(
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository,
        RouterInterface $router,
        TemplateMessageProvider $messageProvider,
        UnilendMailer $mailer,
        NotificationManager $notificationManager
    ) {
        $this->temporaryLinksLoginRepository = $temporaryLinksLoginRepository;
        $this->router                        = $router;
        $this->messageProvider               = $messageProvider;
        $this->mailer                        = $mailer;
        $this->notificationManager           = $notificationManager;
    }

    /**
     * @param Clients $inviter
     * @param Clients $invitee
     * @param Project $project
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function notifyInvited(Clients $inviter, Clients $invitee, Project $project): int
    {
        if (ProjectStatus::STATUS_PUBLISHED === $project->getCurrentStatus()->getStatus()) {
            $this->notificationManager->createProjectPublication($project, $invitee);

            if (ClientsStatus::STATUS_INVITED === $invitee->getCurrentStatus()->getStatus()) {
                return $this->notifyNewClientInvited($inviter, $invitee, $project);
            }

            return $this->notifyInvitedToProject($inviter, $invitee, $project);
        }

        return 0;
    }

    /**
     * @param Clients $inviter
     * @param Clients $invitee
     * @param Project $project
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function notifyNewClientInvited(Clients $inviter, Clients $invitee, Project $project): int
    {
        if (ProjectStatus::STATUS_PUBLISHED === $project->getCurrentStatus()->getStatus()) {
            $token = $this->temporaryLinksLoginRepository->findOneBy(['idClient' => $invitee]);

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
                    'securityToken' => $token->getToken(),
                    'project'       => $project->getSlug(),
                ], RouterInterface::ABSOLUTE_URL),
            ];

            $message = $this->messageProvider->newMessage('project-invitation-new-user', $keywords);
            $message->setTo($invitee->getEmail());

            return $this->mailer->send($message);
        }

        return 0;
    }

    /**
     * @param Clients $inviter
     * @param Clients $invitee
     * @param Project $project
     *
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function notifyInvitedToProject(Clients $inviter, Clients $invitee, Project $project): int
    {
        if (ProjectStatus::STATUS_PUBLISHED === $project->getCurrentStatus()->getStatus()) {
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

        return 0;
    }
}
