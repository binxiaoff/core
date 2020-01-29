<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipationContact;

use Swift_Mailer;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Entity\Clients;
use Unilend\Entity\Companies;
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
    public function sendInvitation(ProjectParticipationContact $contact)
    {
        $templateId           = false;
        $projectParticipation = $contact->getProjectParticipation();
        $company              = $projectParticipation->getCompany();
        $client               = $contact->getClient();
        $project              = $projectParticipation->getProject();

        $arranger = $project->getArranger();

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
                'token' => 'afzd',
            ],
        ];

        if (ProjectStatus::STATUS_PUBLISHED === $project->getCurrentStatus()->getStatus()) {
            $templateId = 'publication' . $this->getVariation($company, $client);
        }

        if (ProjectStatus::STATUS_INTERESTS_COLLECTED === $project->getCurrentStatus()->getStatus()) {
            $templateId = 'syndication' . $this->getVariation($company, $client);
        }

        if ($templateId) {
            $message = $this->templateMessageProvider->newMessage($templateId, $context)
                ->setTo($client->getEmail())
            ;

            $this->mailer->send($message);
        }
    }

    /**
     * @param Companies $company
     * @param Clients   $client
     *
     * @return string
     */
    private function getVariation(Companies $company, Clients $client): string
    {
        if ($company->isProspect()) {
            return '-prospect-company';
        }

        if ($company->hasSigned() && $client->isInvited()) {
            return '-uninitialized-user';
        }

        return '';
    }
}
