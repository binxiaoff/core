<?php

declare(strict_types=1);

namespace Unilend\Syndication\Service\ProjectParticipation;

use JsonException;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Core\SwiftMailer\MailjetMessage;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectParticipationStatus;

class ProjectParticipationNotifier
{
    private Swift_Mailer $mailer;
    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @param Swift_Mailer    $mailer
     * @param RouterInterface $router
     */
    public function __construct(Swift_Mailer $mailer, RouterInterface $router)
    {
        $this->mailer = $mailer;
        $this->router = $router;
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

        $message = (new MailjetMessage())
            ->setTo($submitterClient->getEmail())
            ->setTemplateId(MailjetMessage::TEMPLATE_PARTICIPANT_REPLY)
            ->setVars(
                [
                    'front_projectForm_URL' => $this->router->generate('front_projectForm', ['projectPublicId' => $project->getPublicId()], RouterInterface::ABSOLUTE_URL),
                    'project_riskGroupName' =>  $project->getRiskGroupName(),
                    'project_title' => $project->getTitle(),
                    'participant_displayName' => $projectParticipation->getParticipant()->getDisplayName(),
                    'client_firstName' => $submitterClient->getFirstName(),
                ]
            );

        $this->mailer->send($message);
    }
}
