<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipationMember;

use Exception;
use Swift_Mailer;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{Clients, Company, Project, ProjectParticipationMember, ProjectStatus};
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

        $participation = $projectParticipation->getParticipant();
        $project       = $projectParticipation->getProject();

        $client     = $projectParticipationMember->getStaff()->getClient();
        $templateId = $this->getTemplateId($project, $participation, $client);

        $temporaryToken = null;
        if ($client->isInitializationNeeded()) {
            $temporaryToken = $this->temporaryTokenGenerator->generateUltraLongToken($client);
        }

        $context = [
            'client' => [
                'firstName' => $client->getFirstName(),
                'hash'      => $client->getPublicId(),
            ],
            'submitterCompany' => [
                'name' => $project->getSubmitterCompany()->getName(),
            ],
            'project' => [
                'name' => $project->getTitle(),
                'hash' => $project->getPublicId(),
            ],
            'temporaryToken' => [
                'token' => $temporaryToken ? $temporaryToken->getToken() : '',
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
     * @param Company $company
     * @param Clients $client
     *
     * @return string|null
     */
    private function getTemplateId(Project $project, Company $company, Clients $client): ?string
    {
        $templateId = null;

        if (ProjectStatus::STATUS_INTEREST_EXPRESSION === $project->getCurrentStatus()->getStatus()) {
            if ($company->isProspect()) {
                $templateId = 'publication-prospect-company';
            }

            if ($company->hasSigned()) {
                $templateId = $client->isInitializationNeeded() ? 'publication-uninitialized-user' : 'publication';
            }
        }

        if (ProjectStatus::STATUS_PARTICIPANT_REPLY === $project->getCurrentStatus()->getStatus()) {
            if ($company->isProspect()) {
                $templateId = 'syndication-prospect-company';
            }

            if ($company->hasSigned()) {
                $templateId = $client->isInitializationNeeded() ? 'syndication-uninitialized-user' : 'syndication';
            }
        }

        return $templateId;
    }
}
