<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\ProjectParticipation;

use JsonException;
use KLS\Core\SwiftMailer\MailjetMessage;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationStatus;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;

class ProjectParticipationNotifier
{
    private RouterInterface $router;
    private Swift_Mailer $mailer;

    public function __construct(RouterInterface $router, Swift_Mailer $mailer)
    {
        $this->router = $router;
        $this->mailer = $mailer;
    }

    /**
     * @throws JsonException
     */
    public function notifyParticipantReply(ProjectParticipation $projectParticipation): void
    {
        $project = $projectParticipation->getProject();

        if (
            $projectParticipation->getParticipant() === $project->getSubmitterCompany()
            || \in_array(
                $projectParticipation->getCurrentStatus()->getStatus(),
                [ProjectParticipationStatus::STATUS_CREATED, ProjectParticipationStatus::STATUS_ARCHIVED_BY_ARRANGER],
                true
            )
        ) {
            return;
        }

        $submitterUser = $project->getSubmitterUser();

        $message = (new MailjetMessage())
            ->setTo($submitterUser->getEmail())
            ->setTemplateId(MailjetMessage::TEMPLATE_PARTICIPANT_REPLY)
            ->setVars([
                'front_projectForm_URL'   => $this->router->generate('front_projectForm', ['projectPublicId' => $project->getPublicId()], RouterInterface::ABSOLUTE_URL),
                'project_riskGroupName'   => $project->getRiskGroupName(),
                'project_title'           => $project->getTitle(),
                'participant_displayName' => $projectParticipation->getParticipant()->getDisplayName(),
                'client_firstName'        => $submitterUser->getFirstName() ?? '',
            ])
        ;

        $this->mailer->send($message);
    }
}
