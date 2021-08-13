<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\ProjectParticipationMember;

use Exception;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\TemporaryToken;
use KLS\Core\Service\TemporaryTokenGenerator;
use KLS\Core\SwiftMailer\MailjetMessage;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;

class ProjectParticipationMemberNotifier
{
    private RouterInterface $router;
    private Swift_Mailer $mailer;
    private TemporaryTokenGenerator $temporaryTokenGenerator;

    public function __construct(RouterInterface $router, Swift_Mailer $mailer, TemporaryTokenGenerator $temporaryTokenGenerator)
    {
        $this->router                  = $router;
        $this->mailer                  = $mailer;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
    }

    /**
     * @throws Exception
     */
    public function notifyMemberAdded(ProjectParticipationMember $projectParticipationMember): void
    {
        $projectParticipation = $projectParticipationMember->getProjectParticipation();

        // We notify only other users than the current user.
        // For the arranger, we should not notify anyone in his entity. But it is not yet the case. We will review this part in V2.
        if ($projectParticipationMember->getAddedBy() === $projectParticipationMember->getStaff()) {
            return;
        }

        $project = $projectParticipation->getProject();

        if (false === $project->isPublished() || $projectParticipation->getParticipant()->hasRefused()) {
            return;
        }

        if ($project->getArranger() === $projectParticipation->getParticipant()) {
            return;
        }

        $user       = $projectParticipationMember->getStaff()->getUser();
        $templateId = $this->getTemplateId($project, $projectParticipationMember->getStaff());

        if (null === $templateId) {
            return;
        }

        $temporaryToken = null;
        if ($user->isInitializationNeeded()) {
            $temporaryToken = $this->temporaryTokenGenerator->generateUltraLongToken($user);
        }

        $vars = [
            'temporaryToken_token'        => ($temporaryToken instanceof TemporaryToken) ? $temporaryToken->getToken() : false,
            'front_viewParticipation_URL' => $this->router->generate(
                'front_viewParticipation',
                [
                    'projectParticipationPublicId' => $projectParticipation->getPublicId(),
                ],
                RouterInterface::ABSOLUTE_URL
            ),
            'front_initialAccount_URL' => ($temporaryToken instanceof TemporaryToken) ? $this->router->generate(
                'front_initialAccount',
                [
                    'temporaryTokenPublicId' => $temporaryToken->getToken(),
                    'userPublicId'           => $user->getPublicId(),
                ],
                RouterInterface::ABSOLUTE_URL
            ) : false,
            'front_home'                                   => $this->router->generate('front_home'),
            'front_home_URL'                               => $this->router->generate('front_home'),
            'project_riskGroupName'                        => $project->getRiskGroupName(),
            'project_title'                                => $project->getTitle(),
            'projectParticipation_participant_displayName' => $projectParticipation->getParticipant()->getDisplayName(),
            'arranger_displayName'                         => $project->getSubmitterCompany()->getDisplayName(),
            'client_firstName'                             => $user->getFirstName() ?? '',
        ];

        $message = (new MailjetMessage())
            ->setTo($user->getEmail())
            ->setTemplateId($templateId)
            ->setVars($vars)
        ;

        $this->mailer->send($message);
    }

    /**
     * @todo needs to be refactored
     */
    private function getTemplateId(Project $project, Staff $staff): ?int
    {
        $templateId = null;
        // In the actual habilitation context, the staff company is the same as the participant company
        $participant = $staff->getCompany();
        $user        = $staff->getUser();

        if (ProjectStatus::STATUS_INTEREST_EXPRESSION === $project->getCurrentStatus()->getStatus()) {
            $templateId = MailjetMessage::TEMPLATE_ARRANGER_INVITATION_EXTERNAL_BANK;
            if ($participant->isCAGMember()) {
                if ($participant->isProspect()) {
                    $templateId = MailjetMessage::TEMPLATE_PUBLICATION_PROSPECT_COMPANY;
                }

                if ($participant->hasSigned()) {
                    $templateId = $user->isInitializationNeeded() ? MailjetMessage::TEMPLATE_PUBLICATION_UNINITIALIZED_USER : MailjetMessage::TEMPLATE_PUBLICATION;
                }
            }
        }

        if (ProjectStatus::STATUS_PARTICIPANT_REPLY === $project->getCurrentStatus()->getStatus()) {
            $templateId = MailjetMessage::TEMPLATE_ARRANGER_INVITATION_EXTERNAL_BANK;
            if ($participant->isCAGMember()) {
                if ($participant->isProspect()) {
                    $templateId = MailjetMessage::TEMPLATE_SYNDICATION_PROSPECT_COMPANY;
                }

                if ($participant->hasSigned()) {
                    $templateId = $user->isInitializationNeeded() ? MailjetMessage::TEMPLATE_SYNDICATION_UNINITIALIZED_USER : MailjetMessage::TEMPLATE_SYNDICATION;
                }
            }
        }

        return $templateId;
    }
}
