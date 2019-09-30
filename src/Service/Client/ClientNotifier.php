<?php

declare(strict_types=1);

namespace Unilend\Service\Client;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Swift_Mailer;
use Swift_RfcComplianceException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{Clients, ClientsStatus, Project, ProjectStatus, TemporaryToken};
use Unilend\Repository\TemporaryTokenRepository;
use Unilend\Service\NotificationManager;
use Unilend\SwiftMailer\TemplateMessageProvider;

class ClientNotifier
{
    /** @var TemporaryTokenRepository */
    private $temporaryTokenRepository;
    /** @var RouterInterface */
    private $router;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var Swift_Mailer */
    private $mailer;
    /** @var NotificationManager */
    private $notificationManager;

    /**
     * @param TemporaryTokenRepository $temporaryTokenRepository
     * @param RouterInterface          $router
     * @param TemplateMessageProvider  $messageProvider
     * @param Swift_Mailer             $mailer
     * @param NotificationManager      $notificationManager
     */
    public function __construct(
        TemporaryTokenRepository $temporaryTokenRepository,
        RouterInterface $router,
        TemplateMessageProvider $messageProvider,
        Swift_Mailer $mailer,
        NotificationManager $notificationManager
    ) {
        $this->temporaryTokenRepository = $temporaryTokenRepository;
        $this->router                   = $router;
        $this->messageProvider          = $messageProvider;
        $this->mailer                   = $mailer;
        $this->notificationManager      = $notificationManager;
    }

    /**
     * @param Clients $inviter
     * @param Clients $invitee
     * @param Project $project
     *
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws Swift_RfcComplianceException
     * @throws SyntaxError
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
     * @throws OptimisticLockException
     * @throws Swift_RfcComplianceException
     * @throws Exception
     * @throws ORMException
     *
     * @return int
     */
    public function notifyNewClientInvited(Clients $inviter, Clients $invitee, Project $project): int
    {
        if (ProjectStatus::STATUS_PUBLISHED === $project->getCurrentStatus()->getStatus()) {
            $token = $this->temporaryTokenRepository->findOneBy(['idClient' => $invitee]);

            if ($token && $token->isValid()) {
                $token->extendLong();
                $this->temporaryTokenRepository->save($token);
            } else {
                $token = $this->temporaryTokenRepository->generateLongTemporaryToken($invitee);
            }

            $keywords = [
                'inviterName'    => $inviter->getLastName() . ' ' . $inviter->getFirstName(),
                'project'        => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
                'initAccountUrl' => $this->router->generate('project_invitation', [
                    'securityToken' => $token->getToken(),
                    'slug'          => $project->getSlug(),
                ], RouterInterface::ABSOLUTE_URL),
            ];

            $message = $this->messageProvider->newMessage('invite-guest', $keywords);
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
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
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
