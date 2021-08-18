<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\Project\SlackNotifier;

use InvalidArgumentException;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use Nexy\Slack\Attachment;
use Nexy\Slack\AttachmentField;
use Nexy\Slack\Client as Slack;
use Nexy\Slack\MessageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectUpdateNotifier implements ProjectNotifierInterface
{
    private const PROJECT_STATUS_UPDATED = [
        ProjectStatus::STATUS_INTEREST_EXPRESSION,
        ProjectStatus::STATUS_PARTICIPANT_REPLY,
        ProjectStatus::STATUS_ALLOCATION,
        ProjectStatus::STATUS_SYNDICATION_CANCELLED,
        ProjectStatus::STATUS_SYNDICATION_FINISHED,
    ];
    private Slack $slack;
    private TranslatorInterface $translator;

    public function __construct(
        Slack $client,
        TranslatorInterface $translator
    ) {
        $this->slack      = $client;
        $this->translator = $translator;
    }

    /**
     * @throws \Http\Client\Exception
     * @throws \Nexy\Slack\Exception\SlackApiException
     */
    public function notify(Project $project): void
    {
        if (\in_array($project->getCurrentStatus()->getStatus(), self::PROJECT_STATUS_UPDATED, true)) {
            $this->slack->sendMessage($this->createSlackMessage($project));
        }
    }

    public function createSlackMessage(Project $project): MessageInterface
    {
        $message = $this->slack->createMessage()
            ->enableMarkdown()
            ->setText($this->getSlackMessageText($project))
            ->attach(
                (new Attachment())
                    ->addField(new AttachmentField('Entité', $project->getSubmitterCompany()->getDisplayName(), true))
                    ->addField(new AttachmentField('Utilisateur', $project->getSubmitterUser()->getEmail(), true))
            )
        ;

        if (ProjectStatus::STATUS_SYNDICATION_CANCELLED === $project->getCurrentStatus()->getStatus()) {
            return $message;
        }

        $statusSendMessageWithParameters = [
            ProjectStatus::STATUS_INTEREST_EXPRESSION,
            ProjectStatus::STATUS_PARTICIPANT_REPLY,
            ProjectStatus::STATUS_ALLOCATION,
            ProjectStatus::STATUS_SYNDICATION_FINISHED,
        ];

        if (\in_array($project->getCurrentStatus()->getStatus(), $statusSendMessageWithParameters, true)) {
            $participants         = [];
            $archivedParticipants = [];

            /** @var ProjectParticipation $participation */
            foreach ($project->getProjectParticipations() as $participation) {
                if ($participation->isActive()) {
                    $participants[] = $participation->getParticipant()->getDisplayName() . ': ' .
                        $participation->getInterestRequest()->getMoney()->getAmount() . $participation->getInterestRequest()->getMoney()->getCurrency();

                    continue;
                }
                $archivedParticipants[] = $participation->getParticipant()->getDisplayName() . ': ' .
                    $this->translator->trans('project-participation-status.' . $participation->getCurrentStatus()->getStatus());
            }
            $message->attach(
                (new Attachment())
                    ->addField(new AttachmentField('Participants', \implode(', ', $participants), true))
                    ->addField(new AttachmentField('Participants archivés', \implode(', ', $archivedParticipants), true))
            );
        }

        return $message;
    }

    public function getSlackMessageText(Project $project): string
    {
        switch ($project->getCurrentStatus()->getStatus()) {
            case ProjectStatus::STATUS_INTEREST_EXPRESSION: //ok
                return 'Arrangement: les sollicitations des marques d\'intérêt ont été envoyées pour le dossier « ' . $project->getTitle() . ' ».';

            case ProjectStatus::STATUS_PARTICIPANT_REPLY:
                return 'Arrangement : les demandes en réponse ferme ont été envoyées pour le dossier « ' . $project->getTitle() . ' ».';

            case ProjectStatus::STATUS_ALLOCATION:
                return 'Arrangement : le dossier « ' . $project->getTitle() . ' » vient de passer en phase d\'allocation.';

            case ProjectStatus::STATUS_SYNDICATION_FINISHED:
                return 'Arrangement : le dossier « ' . $project->getTitle() . ' » vient d\'être cloturé.';

            case ProjectStatus::STATUS_SYNDICATION_CANCELLED:
                return 'Arrangement : le dossier « ' . $project->getTitle() . ' » vient d\'être annulé.';
        }

        throw new InvalidArgumentException('The project is in an unknown status');
    }
}
