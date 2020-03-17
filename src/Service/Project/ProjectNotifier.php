<?php

declare(strict_types=1);

namespace Unilend\Service\Project;

use Doctrine\ORM\{NoResultException, NonUniqueResultException};
use Http\Client\Exception;
use InvalidArgumentException;
use Nexy\Slack\Exception\SlackApiException;
use Nexy\Slack\{Attachment, AttachmentField, Client, Message};
use Unilend\Entity\{Project, ProjectStatus};
use Unilend\Repository\ProjectRepository;

class ProjectNotifier
{
    /** @var Client */
    private $client;
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var string */
    private $environment;

    /**
     * @param Client            $client
     * @param ProjectRepository $projectRepository
     * @param string            $environment
     */
    public function __construct(Client $client, ProjectRepository $projectRepository, string $environment)
    {
        $this->client            = $client;
        $this->projectRepository = $projectRepository;
        $this->environment       = $environment;
    }

    /**
     * @param Project $project
     *
     * @throws Exception
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws SlackApiException
     */
    public function notifyProjectCreated(Project $project)
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
    public function createSlackMessage(Project $project)
    {
        return $this->client->createMessage()
            ->enableMarkdown()
            ->setText($this->getSlackMessageText($project))
            ->attach(
                (new Attachment())
                    ->addField(new AttachmentField('Entité', $project->getSubmitterCompany()->getName(), true))
                    ->addField(new AttachmentField('Entités invitées', (string) count($project->getProjectParticipations()), true))
                    ->addField(new AttachmentField('Utilisateur', $project->getSubmitterClient()->getEmail(), true))
                    ->addField(new AttachmentField('Utilisateurs invités', (string) $this->projectRepository->countProjectParticipationContact($project), true))
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
            case ProjectStatus::STATUS_REQUESTED:
                return "Le dosier « {$project->getTitle()} » vient d’être créé";
            case ProjectStatus::STATUS_PUBLISHED:
                return "Les sollicitations des marques d'intérêt ont été envoyées pour le dossier « {$project->getTitle()} ».";
            case ProjectStatus::STATUS_INTERESTS_COLLECTED:
                return "Les demandes de réponse ferme ont été envoyées pour le dossier « {$project->getTitle()} ».";
            case ProjectStatus::STATUS_OFFERS_COLLECTED:
                return "Le dossier « {$project->getTitle()} » vient de passer en phase de contractualisation.";
            case ProjectStatus::STATUS_CONTRACTS_SIGNED:
                return "Le dossier « {$project->getTitle()} » vient d‘être clos.";
            case ProjectStatus::STATUS_REPAID:
                return '';
        }

        throw new InvalidArgumentException('The project is in an unknown status');
    }
}
