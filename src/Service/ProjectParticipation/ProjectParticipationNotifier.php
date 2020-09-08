<?php

namespace Unilend\Service\ProjectParticipation;

use Swift_Mailer;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Entity\ProjectParticipation;
use Unilend\SwiftMailer\TemplateMessageProvider;

class ProjectParticipationNotifier
{
    private TemplateMessageProvider $templateMessageProvider;

    private Swift_Mailer $mailer;

    /**
     * @param TemplateMessageProvider $templateMessageProvider
     * @param Swift_Mailer            $mailer
     */
    public function __construct(TemplateMessageProvider $templateMessageProvider, Swift_Mailer $mailer)
    {
        $this->templateMessageProvider = $templateMessageProvider;
        $this->mailer = $mailer;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function notifyParticipantReply(ProjectParticipation $projectParticipation)
    {
        $project = $projectParticipation->getProject();

        $submitterClient = $project->getSubmitterClient();

        $message = $this->templateMessageProvider->newMessage('participant-reply', [
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
        $message->setTo($submitterClient->getEmail());
        $this->mailer->send($message);
    }
}
