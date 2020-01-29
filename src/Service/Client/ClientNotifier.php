<?php

declare(strict_types=1);

namespace Unilend\Service\Client;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{ClientStatus, Clients, Project, ProjectStatus};
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
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TemporaryTokenRepository $temporaryTokenRepository
     * @param RouterInterface          $router
     * @param TemplateMessageProvider  $messageProvider
     * @param Swift_Mailer             $mailer
     * @param NotificationManager      $notificationManager
     * @param TranslatorInterface      $translator
     */
    public function __construct(
        TemporaryTokenRepository $temporaryTokenRepository,
        RouterInterface $router,
        TemplateMessageProvider $messageProvider,
        Swift_Mailer $mailer,
        NotificationManager $notificationManager,
        TranslatorInterface $translator
    ) {
        $this->temporaryTokenRepository = $temporaryTokenRepository;
        $this->router                   = $router;
        $this->messageProvider          = $messageProvider;
        $this->mailer                   = $mailer;
        $this->notificationManager      = $notificationManager;
        $this->translator               = $translator;
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
     * @throws SyntaxError
     *
     * @return int
     */
    public function notifyInvited(Clients $inviter, Clients $invitee, Project $project): int
    {
        if (ProjectStatus::STATUS_PUBLISHED === $project->getCurrentStatus()->getStatus()) {
            $this->notificationManager->createProjectPublication($project, $invitee);

            if (ClientStatus::STATUS_INVITED === $invitee->getCurrentStatus()->getStatus()) {
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
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     *
     * @return int
     */
    public function notifyNewClientInvited(Clients $inviter, Clients $invitee, Project $project): int
    {
        if (ProjectStatus::STATUS_PUBLISHED === $project->getCurrentStatus()->getStatus()) {
            $token = $invitee->getLastTemporaryToken();

            if ($token && $token->isValid()) {
                $token->extendLong();
                $this->temporaryTokenRepository->save($token);
            } else {
                $token = $this->temporaryTokenRepository->generateLongTemporaryToken($invitee);
            }

            $keywords = [
                'inviterName'    => $inviter->getLastName() . ' ' . $inviter->getFirstName(),
                'project'        => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
                'initAccountUrl' => '' /*

                // TODO Fix because otherwise the participant can"t be created
                $this->router->generate('project_invitation', [
                    'securityToken' => $token->getToken(),
                    'hash'          => $project->getHash(),
                ], RouterInterface::ABSOLUTE_URL)*/,
            ];
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
            $projectUrl  = $this->router->generate('lender_project_details', ['hash' => $project->getHash()], RouterInterface::ABSOLUTE_URL);
            $projectName = $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle();

            $keywords = [
                'inviterName' => $inviter->getLastName() . ' ' . $inviter->getFirstName(),
                'firstName'   => $invitee->getFirstName(),
                'projectUrl'  => $projectUrl,
                'projectName' => $projectName,
            ];
        }

        return 0;
    }

    /**
     * @param Clients $client
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int
     */
    public function sendAccountCreated(Clients $client): int
    {
        return 1;
    }

    /**
     * @param Clients $client
     * @param array   $changeSet
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int
     */
    public function sendIdentityUpdated(Clients $client, array $changeSet): int
    {
        return 0;

        $changeSet = array_map(function ($field) {
            return $this->translator->trans('mail-identity-updated.' . $field);
        }, $changeSet);
        if (count($changeSet) > 1) {
            $content      = $this->translator->trans('mail-identity-updated.content-message-plural');
            $changeFields = '<ul><li>';
            $changeFields .= implode('</li><li>', $changeSet);
            $changeFields .= '</li></ul>';
        } else {
            $content      = $this->translator->trans('mail-identity-updated.content-message-singular');
            $changeFields = $changeSet[0];
        }

        $keywords = [
            'firstName'    => $client->getFirstName(),
            'content'      => $content,
            'profileUrl'   => $this->router->generate('front_home'),
            'changeFields' => $changeFields,
        ];

        return 0;
    }
}
