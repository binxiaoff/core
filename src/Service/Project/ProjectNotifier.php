<?php

declare(strict_types=1);

namespace Unilend\Service\Project;

use Doctrine\ORM\{NoResultException, NonUniqueResultException};
use Http\Client\Exception;
use InvalidArgumentException;
use JsonException;
use Nexy\Slack\Exception\SlackApiException;
use Nexy\Slack\{Attachment, AttachmentField, Client, MessageInterface};
use Swift_Mailer;
use Unilend\Entity\{Project, ProjectStatus};
use Unilend\Repository\ProjectRepository;
use Unilend\SwiftMailer\MailjetMessage;

class ProjectNotifier
{
    /** @var Client */
    private Client $client;
    /** @var ProjectRepository */
    private ProjectRepository $projectRepository;
    /** @var Swift_Mailer */
    private Swift_Mailer $mailer;

    /**
     * @param Client            $client
     * @param ProjectRepository $projectRepository
     * @param Swift_Mailer      $mailer
     */
    public function __construct(Client $client, ProjectRepository $projectRepository, Swift_Mailer $mailer)
    {
        $this->client            = $client;
        $this->projectRepository = $projectRepository;
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
        $this->client->sendMessage($this->createSlackMessage($project));
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
        $this->client->sendMessage($this->createSlackMessage($project));
    }

    /**
     * @param Project $project
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     *
     * @return MessageInterface
     */
    public function createSlackMessage(Project $project): MessageInterface
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
                return 'Le dossier « ' . $project->getTitle() . ' » est terminé.';
            case ProjectStatus::STATUS_SYNDICATION_CANCELLED:
                return 'Le dossier « ' . $project->getTitle() . ' » est annulé.';
        }

        throw new InvalidArgumentException('The project is in an unknown status');
    }

    /**
     * @param Project $project
     *
     * @return int
     *
     * @throws JsonException
     */
    public function notifyUploaded(Project $project): int
    {
        $sent = 0;

        if (ProjectStatus::STATUS_INTEREST_EXPRESSION > $project->getCurrentStatus()->getStatus()) {
            return $sent;
        }

        foreach ($project->getProjectParticipations() as $participation) {
            if ($participation->getParticipant() !== $project->getSubmitterCompany() && $participation->getParticipant()->hasSigned()) {
                foreach ($participation->getActiveProjectParticipationMembers() as $activeProjectParticipationMember) {
                    $message = (new MailjetMessage())
                        ->setTo($activeProjectParticipationMember->getStaff()->getClient()->getEmail())
                        ->setTemplateId(1)
                        ->setVars([
                            'client' => [
                            'firstName' => $activeProjectParticipationMember->getStaff()->getClient()->getFirstName(),
                            ],
                            'project' => [
                            'arranger'      => $project->getSubmitterCompany()->getDisplayName(),
                            'title'         => $project->getTitle(),
                            'riskGroupName' => $project->getRiskGroupName(),
                            ],
                            'projectParticipation' => [
                            'publicId' => $participation->getPublicId(),
                            ],
                        ]);
                    $sent += $this->mailer->send($message);
                }
            }
        }

        return $sent;
    }
}
