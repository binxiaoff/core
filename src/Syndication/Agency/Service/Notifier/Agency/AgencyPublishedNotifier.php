<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Service\Notifier\Agency;

use Http\Client\Exception;
use KLS\Syndication\Agency\Entity\Project;
use Nexy\Slack\Attachment;
use Nexy\Slack\AttachmentField;
use Nexy\Slack\Client as Slack;
use Nexy\Slack\Exception\SlackApiException;
use Nexy\Slack\MessageInterface;

class AgencyPublishedNotifier
{
    private Slack $slack;

    public function __construct(Slack $client)
    {
        $this->slack = $client;
    }

    /**
     * @throws Exception
     * @throws SlackApiException
     */
    public function notify(Project $project): void
    {
        $this->slack->sendMessage($this->createSlackMessage($project));
    }

    private function createSlackMessage(Project $project): MessageInterface
    {
        return $this->slack->createMessage()->enableMarkdown()
            ->setText('*Agency :* Le dossier "' . $project->getTitle() . '" vient d\'être publié 📤')
            ->attach(
                (new Attachment())
                    ->addField(new AttachmentField(
                        'Entité',
                        $project->getAgentCompany()->getDisplayName(),
                        true
                    ))
                    ->addField(new AttachmentField(
                        'Utilisateur',
                        $project->getAddedBy()->getUser()->getEmail(),
                        true
                    ))
            )
            ;
    }
}
