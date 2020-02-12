<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipationContact;

use Exception;
use LogicException;
use Swift_Mailer;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{Clients, Companies, Project, ProjectParticipationContact, ProjectStatus, TemporaryToken};
use Unilend\Repository\TemporaryTokenRepository;
use Unilend\SwiftMailer\TemplateMessageProvider;

class ProjectParticipationContactNotifier
{
    /** @var Swift_Mailer */
    private $mailer;
    /** @var TemplateMessageProvider */
    private $templateMessageProvider;
    /** @var TemporaryTokenRepository */
    private $temporaryTokenRepository;

    /**
     * @param TemplateMessageProvider  $templateMessageProvider
     * @param Swift_Mailer             $mailer
     * @param TemporaryTokenRepository $temporaryTokenRepository
     */
    public function __construct(TemplateMessageProvider $templateMessageProvider, Swift_Mailer $mailer, TemporaryTokenRepository $temporaryTokenRepository)
    {
        $this->mailer                   = $mailer;
        $this->templateMessageProvider  = $templateMessageProvider;
        $this->temporaryTokenRepository = $temporaryTokenRepository;
    }

    /**
     * @param ProjectParticipationContact $contact
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function sendInvitation(ProjectParticipationContact $contact): void
    {
        $projectParticipation = $contact->getProjectParticipation();
        $company              = $projectParticipation->getCompany();
        $client               = $contact->getClient();
        $project              = $projectParticipation->getProject();

        $templateId = $this->getTemplateId($project, $company, $client);

        $arranger = $project->getArranger();

        if (null === $arranger) {
            throw new LogicException('The arranger should not be null');
        }

        $temporaryToken = null;
        if ($client->isInitializationNeeded()) {
            $temporaryToken = TemporaryToken::generateMediumToken($client);
            $this->temporaryTokenRepository->persist($temporaryToken);
        }

        $context = [
            'client' => [
                'firstName' => $client->getFirstName(),
                'hash'      => $client->getHash(),
            ],
            'arranger' => [
                'name' => $arranger->getCompany()->getName(),
            ],
            'project' => [
                'name' => $project->getTitle(),
                'hash' => $project->getHash(),
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
     * @param Project   $project
     * @param Companies $company
     * @param Clients   $client
     *
     * @return string|null
     */
    private function getTemplateId(Project $project, Companies $company, Clients $client): ?string
    {
        $templateId = null;

        if (ProjectStatus::STATUS_PUBLISHED === $project->getCurrentStatus()->getStatus()) {
            if ($company->isProspect()) {
                $templateId = 'publication-prospect-company';
            }

            if ($company->hasSigned()) {
                $templateId = $client->isInitializationNeeded() ? 'publication-uninitialized-user' : 'publication';
            }
        }

        if (ProjectStatus::STATUS_INTERESTS_COLLECTED === $project->getCurrentStatus()->getStatus()) {
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
