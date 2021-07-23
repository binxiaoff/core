<?php

declare(strict_types=1);

namespace Unilend\Agency\Notifier;

use Exception;
use JsonException;
use Swift_Mailer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Agency\Entity\AbstractProjectMember;
use Unilend\Core\Entity\TemporaryToken;
use Unilend\Core\Repository\TemporaryTokenRepository;
use Unilend\Core\SwiftMailer\MailjetMessage;

class ProjectMemberNotifier
{
    private RouterInterface $router;

    private TemporaryTokenRepository $temporaryTokenRepository;
    private Swift_Mailer            $mailer;

    public function __construct(
        RouterInterface $router,
        Swift_Mailer $mailer,
        TemporaryTokenRepository $temporaryTokenRepository
    ) {
        $this->router                   = $router;
        $this->temporaryTokenRepository = $temporaryTokenRepository;
        $this->mailer                   = $mailer;
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function notifyProjectPublication(AbstractProjectMember $projectMember)
    {
        $user = $projectMember->getUser();

        $message = (new MailjetMessage());

        $vars = [
            'projectRiskGroupName'     => $projectMember->getProject()->getRiskGroupName(),
            'projectTitle'             => $projectMember->getProject()->getTitle(),
            'agentDisplayName'         => $projectMember->getProject()->getAgent()->getDisplayName(),
            'lastName'                 => $user->getLastName(),
            'firstName'                => $user->getFirstName(),
            'temporaryToken_token'     => '',
            'front_initialAccount_URL' => '',
            'front_agency_project_URL' => $projectMember->getProjectFrontUrl($this->router),
        ];

        if ($user->isInitializationNeeded()) {
            // Potentially, the same user might receive at the same time multiple email concerning multiple borrower
            // The temporaryToken should be the same
            $temporaryToken = $this->temporaryTokenRepository->findOneActiveByUser($user) ?? TemporaryToken::generateMediumToken($user);

            $temporaryToken->extendMedium();

            $this->temporaryTokenRepository->save($temporaryToken);

            $vars['temporaryToken_token']     = $temporaryToken->getToken();
            $vars['front_initialAccount_URL'] = $this->router->generate(
                'front_initialAccount',
                [
                    'temporaryTokenPublicId' => $temporaryToken->getToken(),
                    'userPublicId'           => $user->getPublicId(),
                    'redirect'               => $this->router->generate('front_agencyDashboard', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        $message->setTemplateId($projectMember::getProjectPublicationNotificationMailjetTemplateId());
        $message->setVars($vars);
        $message->setTo($user->getEmail());

        $this->mailer->send($message);
    }
}
