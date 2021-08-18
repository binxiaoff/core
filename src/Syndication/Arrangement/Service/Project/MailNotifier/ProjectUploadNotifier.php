<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\Project\MailNotifier;

use KLS\Core\SwiftMailer\MailjetMessage;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;

class ProjectUploadNotifier
{
    private Swift_Mailer $mailer;
    private RouterInterface $router;

    public function __construct(
        Swift_Mailer $mailer,
        RouterInterface $router
    ) {
        $this->mailer = $mailer;
        $this->router = $router;
    }

    public function notify(Project $project): int
    {
        $sent = 0;

        if (ProjectStatus::STATUS_INTEREST_EXPRESSION > $project->getCurrentStatus()->getStatus()) {
            return $sent;
        }

        foreach ($project->getProjectParticipations() as $participation) {
            if (
                $participation->getParticipant() !== $project->getSubmitterCompany()
                && ($participation->getParticipant()->hasSigned() || false === $participation->getParticipant()->isCAGMember())
            ) {
                foreach ($participation->getActiveProjectParticipationMembers() as $activeProjectParticipationMember) {
                    $message = (new MailjetMessage())
                        ->setTo($activeProjectParticipationMember->getStaff()->getUser()->getEmail())
                        ->setTemplateId(MailjetMessage::TEMPLATE_PROJECT_FILE_UPLOADED)
                        ->setVars([
                            'front_viewParticipation_URL' => $this->router->generate(
                                'front_viewParticipation',
                                ['projectParticipationPublicId' => $participation->getPublicId()],
                                RouterInterface::ABSOLUTE_URL
                            ),
                            'client_firstName'      => $activeProjectParticipationMember->getStaff()->getUser()->getFirstName() ?? '',
                            'project_arranger'      => $project->getSubmitterCompany()->getDisplayName(),
                            'project_title'         => $project->getTitle(),
                            'project_riskGroupName' => $project->getRiskGroupName(),
                        ])
                    ;
                    $sent += $this->mailer->send($message);
                }
            }
        }

        return $sent;
    }
}
