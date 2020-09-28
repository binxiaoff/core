<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipationMember;

use Exception;
use Swift_Mailer;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{Project, ProjectParticipationMember, ProjectStatus, Staff};
use Unilend\Service\TemporaryTokenGenerator;
use Unilend\SwiftMailer\TemplateMessageProvider;

class ProjectParticipationMemberNotifier
{
    /** @var Swift_Mailer */
    private Swift_Mailer $mailer;
    /** @var TemplateMessageProvider */
    private TemplateMessageProvider $templateMessageProvider;
    /** @var TemporaryTokenGenerator */
    private TemporaryTokenGenerator $temporaryTokenGenerator;

    /**
     * @param TemplateMessageProvider $templateMessageProvider
     * @param Swift_Mailer            $mailer
     * @param TemporaryTokenGenerator $temporaryTokenGenerator
     */
    public function __construct(TemplateMessageProvider $templateMessageProvider, Swift_Mailer $mailer, TemporaryTokenGenerator $temporaryTokenGenerator)
    {
        $this->mailer                  = $mailer;
        $this->templateMessageProvider = $templateMessageProvider;
        $this->temporaryTokenGenerator = $temporaryTokenGenerator;
    }

    /**
     * @param ProjectParticipationMember $projectParticipationMember
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
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
            'client' => [
                'firstName' => $client->getFirstName(),
                'publicId'  => $client->getPublicId(),
            ],
            'arranger' => [
                'displayName' => $project->getSubmitterCompany()->getDisplayName(),
            ],
            'project' => [
                'title' => $project->getTitle(),
                'riskGroupName' => $project->getRiskGroupName(),
                'publicId' => $project->getPublicId(),
            ],
            'projectParticipation' => [
                'publicId'    => $projectParticipation->getPublicId(),
                'participant' => [
                    'displayName' => $projectParticipation->getParticipant()->getDisplayName(),
                ],
            ],
            'temporaryToken' => [
                'token' => $temporaryToken ? $temporaryToken->getToken() : false,
            ],
        ];

        if ($templateId) {
            $message = $this->templateMessageProvider->newMessage($templateId, $context)
                ->setTo($client->getEmail())
            ;

            $this->mailer->send($message);
        }
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @return string|null
     */
    private function getTemplateId(Project $project, Staff $staff): ?string
    {
        $templateId  = null;
        // In the actual habilitation context, the staff company is the same as the participant company
        $participant = $staff->getCompany();
        $client      = $staff->getClient();

        if (ProjectStatus::STATUS_INTEREST_EXPRESSION === $project->getCurrentStatus()->getStatus()) {
            if ($participant->isProspect()) {
                $templateId = 'publication-prospect-company';
            }

            if ($participant->hasSigned()) {
                $templateId = $client->isInitializationNeeded() ? 'publication-uninitialized-user' : 'publication';
            }
        }

        if (ProjectStatus::STATUS_PARTICIPANT_REPLY === $project->getCurrentStatus()->getStatus()) {
            if ($participant->isCAGMember()) {
                if ($participant->isProspect()) {
                    $templateId = 'syndication-prospect-company';
                }

                if ($participant->hasSigned()) {
                    $templateId = $client->isInitializationNeeded() ? 'syndication-uninitialized-user' : 'syndication';
                }
            } else {
                $templateId = 'arranger-invitation-external-bank';
            }
        }

        return $templateId;
    }
}
