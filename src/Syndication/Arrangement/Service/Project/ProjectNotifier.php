<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\Project;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Http\Client\Exception;
use InvalidArgumentException;
use JsonException;
use KLS\Core\SwiftMailer\MailjetMessage;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use Nexy\Slack\Attachment;
use Nexy\Slack\AttachmentField;
use Nexy\Slack\Client as Slack;
use Nexy\Slack\Exception\SlackApiException;
use Nexy\Slack\MessageInterface;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectNotifier
{
    private Slack $slack;
    private Swift_Mailer $mailer;
    private RouterInterface $router;
    private TranslatorInterface $translator;

    public function __construct(
        Slack $client,
        Swift_Mailer $mailer,
        RouterInterface $router,
        TranslatorInterface $translator
    ) {
        $this->slack      = $client;
        $this->mailer     = $mailer;
        $this->router     = $router;
        $this->translator = $translator;
    }

    /**
     * @throws Exception
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws SlackApiException
     */
    public function notifyProjectCreated(Project $project): void
    {
        $this->slack->sendMessage($this->createSlackMessage($project));
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws Exception
     * @throws SlackApiException
     */
    public function notifyProjectStatusChanged(Project $project): void
    {
        $this->slack->sendMessage($this->createSlackMessage($project));
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

        $statusConcerned = [
            ProjectStatus::STATUS_INTEREST_EXPRESSION,
            ProjectStatus::STATUS_PARTICIPANT_REPLY,
            ProjectStatus::STATUS_ALLOCATION,
            ProjectStatus::STATUS_SYNDICATION_FINISHED,
        ];

        if (\in_array($project->getCurrentStatus()->getStatus(), $statusConcerned, true)) {
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
            case ProjectStatus::STATUS_DRAFT:
                return 'Le dosier « ' . $project->getTitle() . ' » vient d’être créé';

            case ProjectStatus::STATUS_INTEREST_EXPRESSION:
                return 'Les sollicitations des marques d\'intérêt ont été envoyées pour le dossier « ' . $project->getTitle() . ' ».';

            case ProjectStatus::STATUS_PARTICIPANT_REPLY:
                return 'Les demandes de réponse ferme ont été envoyées pour le dossier « ' . $project->getTitle() . ' ».';

            case ProjectStatus::STATUS_ALLOCATION:
                return 'Le dossier « ' . $project->getTitle() . ' » vient de passer en phase de contractualisation.';

            case ProjectStatus::STATUS_CONTRACTUALISATION:
                return 'Le dossier « ' . $project->getTitle() . ' » vient d‘être clos.';

            case ProjectStatus::STATUS_SYNDICATION_FINISHED:
                return 'Le dossier « ' . $project->getTitle() . ' » est terminé.';

            case ProjectStatus::STATUS_SYNDICATION_CANCELLED:
                return 'Le dossier « ' . $project->getTitle() . ' » est annulé.';
        }

        throw new InvalidArgumentException('The project is in an unknown status');
    }

    /**
     * @throws JsonException
     */
    public function notifyUploaded(Project $project): int
    {
        $sent = 0;

        if (ProjectStatus::STATUS_INTEREST_EXPRESSION > $project->getCurrentStatus()->getStatus()) {
            return $sent;
        }

        foreach ($project->getProjectParticipations() as $participation) {
            if (
                $participation->getParticipant() !== $project->getSubmitterCompany()
                && ($participation->getParticipant()->hasSigned() || false === $participation->getParticipant()->isCAGMember())
            ) {
                foreach ($participation->getActiveProjectParticipationMembers() as $activeProjectParticipationMember) {
                    $message = (new MailjetMessage())
                        ->setTo($activeProjectParticipationMember->getStaff()->getUser()->getEmail())
                        ->setTemplateId(MailjetMessage::TEMPLATE_PROJECT_FILE_UPLOADED)
                        ->setVars([
                            'front_viewParticipation_URL' => $this->router->generate(
                                'front_viewParticipation',
                                ['projectParticipationPublicId' => $participation->getPublicId()],
                                RouterInterface::ABSOLUTE_URL
                            ),
                            'client_firstName'      => $activeProjectParticipationMember->getStaff()->getUser()->getFirstName() ?? '',
                            'project_arranger'      => $project->getSubmitterCompany()->getDisplayName(),
                            'project_title'         => $project->getTitle(),
                            'project_riskGroupName' => $project->getRiskGroupName(),
                        ])
                    ;
                    $sent += $this->mailer->send($message);
                }
            }
        }

        return $sent;
    }
}
