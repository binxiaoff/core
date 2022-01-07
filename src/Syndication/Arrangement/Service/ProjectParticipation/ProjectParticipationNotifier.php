<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\ProjectParticipation;

use KLS\Core\Mailer\MailjetMessage;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ProjectParticipationNotifier
{
    private RouterInterface $router;
    private MailerInterface $mailer;
    private LoggerInterface $logger;

    public function __construct(RouterInterface $router, MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->router = $router;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    public function notifyParticipantReply(ProjectParticipation $projectParticipation): void
    {
        $project = $projectParticipation->getProject();

        if (
            $projectParticipation->getParticipant() === $project->getSubmitterCompany()
            || ($projectParticipation->getCurrentStatus() && \in_array(
                $projectParticipation->getCurrentStatus()->getStatus(),
                [
                    ProjectParticipationStatus::STATUS_CREATED,
                    ProjectParticipationStatus::STATUS_ARCHIVED_BY_ARRANGER,
                ],
                true
            ))
        ) {
            return;
        }

        $submitterUser = $project->getSubmitterUser();
        $templateId    = MailjetMessage::TEMPLATE_PARTICIPANT_REPLY;

        try {
            $message = (new MailjetMessage())
                ->to($submitterUser->getEmail())
                ->setTemplateId($templateId)
                ->setVars([
                    'front_projectForm_URL' => $this->router->generate(
                        'front_projectForm',
                        ['projectPublicId' => $project->getPublicId()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    'project_riskGroupName'   => $project->getRiskGroupName(),
                    'project_title'           => $project->getTitle(),
                    'participant_displayName' => $projectParticipation->getParticipant()->getDisplayName(),
                    'client_firstName'        => $submitterUser->getFirstName() ?? '',
                ])
            ;

            $this->mailer->send($message);
        } catch (\Throwable $throwable) {
            $this->logger->error(
                \sprintf(
                    'Email sending failed for %s with template id %d. Error: %s',
                    $submitterUser->getEmail(),
                    $templateId,
                    $throwable->getMessage()
                ),
                ['throwable' => $throwable]
            );
        }
    }
}
