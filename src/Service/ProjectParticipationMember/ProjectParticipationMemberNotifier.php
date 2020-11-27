<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipationMember;

use Exception;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Service\TemporaryTokenGenerator;
use Unilend\Core\SwiftMailer\MailjetMessage;
use Unilend\Syndication\Entity\{Project, ProjectParticipationMember, ProjectStatus};

class ProjectParticipationMemberNotifier
{
    /** @var Swift_Mailer */
    private Swift_Mailer $mailer;

    /** @var TemporaryTokenGenerator */
    private TemporaryTokenGenerator $temporaryTokenGenerator;
    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @param Swift_Mailer            $mailer
     * @param TemporaryTokenGenerator $temporaryTokenGenerator
     * @param RouterInterface         $router
     */
    public function __construct(Swift_Mailer $mailer, TemporaryTokenGenerator $temporaryTokenGenerator, RouterInterface $router)
    {
        $this->mailer                  = $mailer;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
        $this->router = $router;
    }

    /**
     * @param ProjectParticipationMember $projectParticipationMember
     *
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

        $client     = $projectParticipationMember->getStaff()->getClient();
        $templateId = $this->getTemplateId($project, $projectParticipationMember->getStaff());

        $temporaryToken = null;
        if ($client->isInitializationNeeded()) {
            $temporaryToken = $this->temporaryTokenGenerator->generateUltraLongToken($client);
        }

        $context = [
            'front_viewParticipation_URL' => $this->router->generate(
                'front_viewParticipation',
                [
                    'projectParticipationPublicId' => $projectParticipation->getPublicId(),
                ],
                RouterInterface::ABSOLUTE_URL
            ),
            'front_initialAccount_URL' => $temporaryToken ? $this->router->generate(
                'front_initialAccount',
                [
                    'temporaryTokenPublicId' => $temporaryToken->getToken(),
                    'clientPublicId' => $client->getPublicId(),
                ],
                RouterInterface::ABSOLUTE_URL
            ) : null,
            'front_home' => $this->router->generate('front_home'),
            'front_home_URL' => $this->router->generate('front_home'),
            'project_riskGroupName' => $project->getRiskGroupName(),
            'project_title' => $project->getTitle(),
            'projectParticipation_participant_displayName' => $projectParticipation->getParticipant()->getDisplayName(),
            'arranger_displayName' => $project->getSubmitterCompany()->getDisplayName(),
            'client_firstName' => $client->getFirstName(),
        ];

        if ($templateId) {
            $message = (new MailjetMessage())
                ->setTo($client->getEmail())
                ->setTemplateId($this->getTemplateId($project, $projectParticipationMember->getStaff()))
                ->setVars($context)
            ;

            $this->mailer->send($message);
        }
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @return int|null
     */
    private function getTemplateId(Project $project, Staff $staff): ?int
    {
        $templateId  = null;
        // In the actual habilitation context, the staff company is the same as the participant company
        $participant = $staff->getCompany();
        $client      = $staff->getClient();

        if (ProjectStatus::STATUS_INTEREST_EXPRESSION === $project->getCurrentStatus()->getStatus()) {
            if ($participant->isProspect()) {
                $templateId = MailjetMessage::TEMPLATE_PUBLICATION_PROSPECT_COMPANY;
            }

            if ($participant->hasSigned()) {
                $templateId = $client->isInitializationNeeded() ? MailjetMessage::TEMPLATE_PUBLICATION_UNINITIALIZED_USER : MailjetMessage::TEMPLATE_PUBLICATION;
            }
        }

        if (ProjectStatus::STATUS_PARTICIPANT_REPLY === $project->getCurrentStatus()->getStatus()) {
            if ($participant->isCAGMember()) {
                if ($participant->isProspect()) {
                    $templateId = MailjetMessage::TEMPLATE_SYNDICATION_PROSPECT_COMPANY;
                }

                if ($participant->hasSigned()) {
                    $templateId = $client->isInitializationNeeded() ? MailjetMessage::TEMPLATE_SYNDICATION_UNINITIALIZED_USER : MailjetMessage::TEMPLATE_SYNDICATION;
                }
            } else {
                $templateId = MailjetMessage::TEMPLATE_ARRANGER_INVITATION_EXTERNAL_BANK;
            }
        }

        return $templateId;
    }
}
