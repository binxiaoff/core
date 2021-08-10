<?php

declare(strict_types=1);

namespace KLS\Agency\Notifier;

use Exception;
use JsonException;
use KLS\Agency\Entity\AbstractProjectMember;
use KLS\Core\Entity\TemporaryToken;
use KLS\Core\Repository\TemporaryTokenRepository;
use KLS\Core\SwiftMailer\MailjetMessage;
use Swift_Mailer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ProjectMemberNotifier
{
    private RouterInterface $router;
    private Swift_Mailer $mailer;
    private TemporaryTokenRepository $temporaryTokenRepository;

    public function __construct(
        RouterInterface $router,
        Swift_Mailer $mailer,
        TemporaryTokenRepository $temporaryTokenRepository
    ) {
        $this->router                   = $router;
        $this->mailer                   = $mailer;
        $this->temporaryTokenRepository = $temporaryTokenRepository;
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function notifyProjectPublication(AbstractProjectMember $projectMember)
    {
        $user    = $projectMember->getUser();
        $project = $projectMember->getProject();

        $vars = [
            'projectRiskGroupName'     => $project->getRiskGroupName(),
            'projectTitle'             => $project->getTitle(),
            'agentDisplayName'         => $project->getAgent()->getDisplayName(),
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

        $message = (new MailjetMessage())
            ->setTemplateId($projectMember::getProjectPublicationNotificationMailjetTemplateId())
            ->setTo($user->getEmail())
            ->setVars($vars)
        ;

        $this->mailer->send($message);
    }
}
