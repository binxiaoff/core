<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\Project\SlackNotifier;

use InvalidArgumentException;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationStatus;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use Nexy\Slack\Attachment;
use Nexy\Slack\AttachmentField;
use Nexy\Slack\Client as Slack;
use Nexy\Slack\MessageInterface;
use NumberFormatter;
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
    private NumberFormatter $formatter;

    public function __construct(
        Slack $client,
        TranslatorInterface $translator,
        NumberFormatter $formatter
    ) {
        $this->slack      = $client;
        $this->translator = $translator;
        $this->formatter  = $formatter;
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
                    $money          = $this->getRelevantParticipationDataByProjectStatus($project, $participation);
                    $participants[] = $participation->getParticipant()->getDisplayName() . ': ' . $money;

                    continue;
                }
                $archivedParticipants[] = $participation->getParticipant()->getDisplayName() . ': ' .
                    $this->translator->trans($this->getArchivedProjectParticipationStatusTranslationKey($participation));
            }

            $message->attach(
                (new Attachment())
                    ->addField(new AttachmentField('Participants', false === empty($participants) ? '• ' . \implode(PHP_EOL . '• ', $participants) : '', true))
                    ->addField(
                        new AttachmentField(
                            'Participants archivés',
                            false === empty($archivedParticipants) ? '• ' . \implode(PHP_EOL . '• ', $archivedParticipants) : ' - ',
                            true
                        )
                    )
            );
        }

        return $message;
    }

    public function getSlackMessageText(Project $project): string
    {
        switch ($project->getCurrentStatus()->getStatus()) {
            case ProjectStatus::STATUS_INTEREST_EXPRESSION: //ok
                return '*Arrangement :* les sollicitations des *marques d\'intérêt* ont été envoyées pour le dossier « ' . $project->getTitle() . ' ».';

            case ProjectStatus::STATUS_PARTICIPANT_REPLY:
                return '*Arrangement :* les demandes en *réponse ferme* ont été envoyées pour le dossier « ' . $project->getTitle() . ' ».';

            case ProjectStatus::STATUS_ALLOCATION:
                return '*Arrangement :* le dossier « ' . $project->getTitle() . ' » vient de passer en *phase d\'allocation*.';

            case ProjectStatus::STATUS_SYNDICATION_FINISHED:
                return '*Arrangement :* le dossier « ' . $project->getTitle() . ' » vient d\'être *cloturé*.';

            case ProjectStatus::STATUS_SYNDICATION_CANCELLED:
                return '*Arrangement :* le dossier « ' . $project->getTitle() . ' » vient d\'être *annulé*.';
        }

        throw new InvalidArgumentException('The project is in an unknown status');
    }

    private function getRelevantParticipationDataByProjectStatus(Project $project, ProjectParticipation $participation): string
    {
        $projectCurrentStatus = $project->getCurrentStatus();

        if ($projectCurrentStatus) {
            switch ($projectCurrentStatus->getStatus()) {
                case ProjectStatus::STATUS_INTEREST_EXPRESSION:
                    $money    = null;
                    $maxMoney = null;
                    if (false === $participation->getInterestRequest()->getMoney()->isNull()) {
                        $money = $this->formatter->formatCurrency(
                            (float) $participation->getInterestRequest()->getMoney()->getAmount(),
                            $participation->getInterestRequest()->getMoney()->getCurrency()
                        );
                    }

                    if (false === $participation->getInterestRequest()->getMaxMoney()->isNull()) {
                        $maxMoney = $this->formatter->formatCurrency(
                            (float) $participation->getInterestRequest()->getMaxMoney()->getAmount(),
                            $participation->getInterestRequest()->getMaxMoney()->getCurrency()
                        );
                    }

                    return \implode(' - ', \array_filter([$money, $maxMoney]));

                case ProjectStatus::STATUS_PARTICIPANT_REPLY:
                    return $participation->getInvitationRequest()->getMoney()->isNull() ? '' :
                        $this->formatter->formatCurrency(
                            (float) $participation->getInvitationRequest()->getMoney()->getAmount(),
                            $participation->getInvitationRequest()->getMoney()->getCurrency()
                        );

                case ProjectStatus::STATUS_ALLOCATION:
                    return $this->formatter->formatCurrency(
                        (float) $participation->getTotalInvitationReply()->getAmount(),
                        $participation->getTotalInvitationReply()->getCurrency()
                    );

                case ProjectStatus::STATUS_SYNDICATION_FINISHED:
                    return $this->formatter->formatCurrency(
                        (float) $participation->getTotalAllocation()->getAmount(),
                        $participation->getTotalAllocation()->getCurrency()
                    );
            }

            throw new \UnexpectedValueException(
                \sprintf('Unexpected %s status value for project %s', $projectCurrentStatus->getStatus(), $project->getPublicId())
            );
        }

        throw new \UnexpectedValueException(\sprintf('No current status available for project %s', $project->getPublicId()));
    }

    private function getArchivedProjectParticipationStatusTranslationKey(ProjectParticipation $participation): string
    {
        $projectParticipationStatus = $participation->getCurrentStatus();

        if ($projectParticipationStatus) {
            switch ($projectParticipationStatus->getStatus()) {
                case ProjectParticipationStatus::STATUS_ARCHIVED_BY_ARRANGER:
                    return 'project-participation-status.-10';

                case ProjectParticipationStatus::STATUS_ARCHIVED_BY_PARTICIPANT:
                    return 'project-participation-status.-20';

                case ProjectParticipationStatus::STATUS_COMMITTEE_REJECTED:
                    return 'project-participation-status.-30';
            }

            throw new \UnexpectedValueException(
                \sprintf('Unexpected %s status value for archived project participation %s', $projectParticipationStatus->getStatus(), $participation->getPublicId())
            );
        }

        throw new \UnexpectedValueException(\sprintf('No current status available for projectParticipation %s', $participation->getPublicId()));
    }
}
