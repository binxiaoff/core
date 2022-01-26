<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Notifier;

use KLS\Core\Entity\TemporaryToken;
use KLS\Core\Mailer\MailjetMessage;
use KLS\Core\Repository\TemporaryTokenRepository;
use KLS\Syndication\Agency\Entity\AbstractProjectMember;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ProjectMemberNotifier
{
    private RouterInterface          $router;
    private MailerInterface          $mailer;
    private TemporaryTokenRepository $temporaryTokenRepository;
    private LoggerInterface          $logger;

    public function __construct(
        RouterInterface $router,
        MailerInterface $mailer,
        TemporaryTokenRepository $temporaryTokenRepository,
        LoggerInterface $logger
    ) {
        $this->router                   = $router;
        $this->mailer                   = $mailer;
        $this->temporaryTokenRepository = $temporaryTokenRepository;
        $this->logger                   = $logger;
    }

    public function notifyProjectPublication(AbstractProjectMember $projectMember): void
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

        $templateId = $projectMember::getProjectPublicationNotificationMailjetTemplateId();

        try {
            if ($user->isInitializationNeeded()) {
                // Potentially, the same user might receive at the same time multiple email concerning multiple borrower
                // The temporaryToken should be the same
                $temporaryToken = $this->temporaryTokenRepository
                    ->findOneActiveByUser($user) ?? TemporaryToken::generateUltraLongToken($user);
                $temporaryToken->extendUltraLong();

                $this->temporaryTokenRepository->save($temporaryToken);

                $vars['temporaryToken_token']     = $temporaryToken->getToken();
                $vars['front_initialAccount_URL'] = $this->router->generate(
                    'front_initialAccount',
                    [
                        'temporaryTokenPublicId' => $temporaryToken->getToken(),
                        'userPublicId'           => $user->getPublicId(),
                        'redirect'               => $this->router->generate(
                            'front_agencyDashboard',
                            [],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }
            $message = (new MailjetMessage())
                ->setTemplateId($projectMember::getProjectPublicationNotificationMailjetTemplateId())
                ->to($user->getEmail())
                ->setVars($vars)
                ;

            $this->mailer->send($message);
        } catch (\Throwable $throwable) {
            $this->logger->error(
                \sprintf(
                    'Email sending failed for %s with template id %d. Error: %s',
                    $user->getEmail(),
                    $templateId,
                    $throwable->getMessage()
                ),
                ['throwable' => $throwable]
            );
        }
    }
}
