<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipation;

use JsonException;
use Swift_Mailer;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationStatus;
use Unilend\SwiftMailer\MailjetMessage;

class ProjectParticipationNotifier
{
    private Swift_Mailer $mailer;

    /**
     * @param Swift_Mailer $mailer
     */
    public function __construct(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @throws JsonException
     */
    public function notifyParticipantReply(ProjectParticipation $projectParticipation): void
    {
        $project = $projectParticipation->getProject();

        if (
            $projectParticipation->getParticipant() === $project->getSubmitterCompany() ||
            \in_array(
                $projectParticipation->getCurrentStatus()->getStatus(),
                [ProjectParticipationStatus::STATUS_CREATED, ProjectParticipationStatus::STATUS_ARCHIVED_BY_ARRANGER],
                true
            )
        ) {
            return;
        }

        $submitterClient = $project->getSubmitterClient();

        $message = (new MailjetMessage())->setTo($submitterClient->getEmail())->setTemplateId(1)->setVars([
            'participant' => [
                'displayName' => $projectParticipation->getParticipant()->getDisplayName(),
            ],
            'client' => [
                'firstName' => $submitterClient->getFirstName(),
            ],
            'project' => [
                'publicId' => $project->getPublicId(),
                'title' => $project->getTitle(),
                'riskGroupName' => $project->getRiskGroupName(),
            ],
        ]);

        $this->mailer->send($message);
    }
}
