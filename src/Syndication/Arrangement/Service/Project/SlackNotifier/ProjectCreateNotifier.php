<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\Project\SlackNotifier;

use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use Nexy\Slack\Attachment;
use Nexy\Slack\AttachmentField;
use Nexy\Slack\Client as Slack;
use Nexy\Slack\MessageInterface;
use NumberFormatter;

class ProjectCreateNotifier
{
    private Slack $slack;
    private NumberFormatter $formatter;

    public function __construct(Slack $client, NumberFormatter $formatter)
    {
        $this->slack     = $client;
        $this->formatter = $formatter;
    }

    /**
     * @throws \Http\Client\Exception
     * @throws \Nexy\Slack\Exception\SlackApiException
     */
    public function notify(Project $project): void
    {
        if (ProjectStatus::STATUS_DRAFT === $project->getCurrentStatus()->getStatus()) {
            $this->slack->sendMessage($this->createSlackMessage($project));
        }
    }

    public function createSlackMessage(Project $project): MessageInterface
    {
        return $this->slack->createMessage()
            ->enableMarkdown()
            ->setText("*Arrangement :* le dossier « {$project->getTitle()} » vient d'être créé")
            ->attach(
                (new Attachment())
                    ->addField(new AttachmentField('Entité', $project->getSubmitterCompany()->getDisplayName(), true))
                    ->addField(new AttachmentField('Utilisateur', $project->getSubmitterUser()->getEmail(), true))
                    ->addField(new AttachmentField(
                        'Montant du projet',
                        $this->formatter->formatCurrency((float) $project->getGlobalFundingMoney()->getAmount(), $project->getGlobalFundingMoney()->getCurrency()),
                        true
                    ))
            )
        ;
    }
}
