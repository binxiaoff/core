<?php

declare(strict_types=1);

namespace Unilend\Service\Project;

use Doctrine\ORM\{NoResultException, NonUniqueResultException};
use Http\Client\Exception;
use InvalidArgumentException;
use Nexy\Slack\Exception\SlackApiException;
use Nexy\Slack\{Attachment, AttachmentField, Client, Message};
use Swift_Mailer;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{Project, ProjectStatus};
use Unilend\Repository\ProjectRepository;
use Unilend\SwiftMailer\TemplateMessageProvider;

class ProjectNotifier
{
    /** @var Client */
    private $client;
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var string */
    private $environment;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var Swift_Mailer */
    private $mailer;

    /**
     * @param Client                  $client
     * @param ProjectRepository       $projectRepository
     * @param string                  $environment
     * @param TemplateMessageProvider $messageProvider
     * @param Swift_Mailer            $mailer
     */
    public function __construct(Client $client, ProjectRepository $projectRepository, string $environment, TemplateMessageProvider $messageProvider, Swift_Mailer $mailer)
    {
        $this->client            = $client;
        $this->projectRepository = $projectRepository;
        $this->environment       = $environment;
        $this->messageProvider   = $messageProvider;
        $this->mailer            = $mailer;
    }

    /**
     * @param Project $project
     *
     * @throws Exception
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws SlackApiException
     */
    public function notifyProjectCreated(Project $project): void
    {
        if ('dev' !== $this->environment) {
            $this->client->sendMessage($this->createSlackMessage($project));
        }
    }

    /**
     * @param Project $project
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws Exception
     * @throws SlackApiException
     */
    public function notifyProjectStatusChanged(Project $project): void
    {
        if ('dev' !== $this->environment) {
            $this->client->sendMessage($this->createSlackMessage($project));
        }
    }

    /**
     * @param Project $project
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     *
     * @return Message
     */
    public function createSlackMessage(Project $project): Message
    {
        return $this->client->createMessage()
            ->enableMarkdown()
            ->setText($this->getSlackMessageText($project))
            ->attach(
                (new Attachment())
                    ->addField(new AttachmentField('Entité', $project->getSubmitterCompany()->getDisplayName(), true))
                    ->addField(new AttachmentField('Entités invitées', (string) count($project->getProjectParticipations()), true))
                    ->addField(new AttachmentField('Utilisateur', $project->getSubmitterClient()->getEmail(), true))
                    ->addField(new AttachmentField('Utilisateurs invités', (string) $this->projectRepository->countProjectParticipationMembers($project), true))
            )
        ;
    }

    /**
     * @param Project $project
     *
     * @return string
     */
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
                return '';
        }

        throw new InvalidArgumentException('The project is in an unknown status');
    }

    /**
     * @param Project $project
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int
     */
    public function notifyUploaded(Project $project): int
    {
        $sent = 0;

        if (ProjectStatus::STATUS_INTEREST_EXPRESSION > $project->getCurrentStatus()->getStatus()) {
            return $sent;
        }

        foreach ($project->getProjectParticipations() as $participation) {
            if ($participation->getParticipant() !== $project->getSubmitterCompany()) {
                foreach ($participation->getActiveProjectParticipationMembers() as $activeProjectParticipationMember) {
                    $message = $this->messageProvider->newMessage('project-file-uploaded', [
                        'client' => [
                            'firstName' => $activeProjectParticipationMember->getStaff()->getClient()->getFirstName(),
                        ],
                        'project' => [
                            'submitterCompany' => $project->getSubmitterCompany()->getDisplayName(),
                            'title'            => $project->getTitle(),
                            'hash'             => $project->getPublicId(),
                        ],
                    ])->setTo($activeProjectParticipationMember->getStaff()->getClient()->getEmail());
                    $sent += $this->mailer->send($message);
                }
            }
        }

        return $sent;
    }
}
