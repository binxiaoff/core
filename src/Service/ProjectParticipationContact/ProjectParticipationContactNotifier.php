<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipationContact;

use Swift_Mailer;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Entity\Clients;
use Unilend\Entity\Companies;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipationContact;
use Unilend\Entity\ProjectStatus;
use Unilend\SwiftMailer\TemplateMessageProvider;

class ProjectParticipationContactNotifier
{
    /** @var Swift_Mailer */
    private $mailer;
    /** @var TemplateMessageProvider */
    private $templateMessageProvider;

    /**
     * @param TemplateMessageProvider $templateMessageProvider
     * @param Swift_Mailer            $mailer
     */
    public function __construct(TemplateMessageProvider $templateMessageProvider, Swift_Mailer $mailer)
    {
        $this->mailer                  = $mailer;
        $this->templateMessageProvider = $templateMessageProvider;
    }

    /**
     * @param ProjectParticipationContact $contact
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
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
            throw new \LogicException('The arranger should not be null');
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
                $templateId = $client->isInvited() ? 'publication-uninitialized-user' : 'publication';
            }
        }

        if (ProjectStatus::STATUS_INTERESTS_COLLECTED === $project->getCurrentStatus()->getStatus()) {
            if ($company->isProspect()) {
                $templateId = 'syndication-prospect-company';
            }

            if ($company->hasSigned()) {
                $templateId = $client->isInvited() ? 'syndication-uninitialized-user' : 'syndication';
            }
        }

        return $templateId;
    }
}
