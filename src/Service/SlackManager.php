<?php

namespace Unilend\Service;

use Http\Client\Exception;
use Nexy\Slack\Client;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Unilend\Entity\{Projects, ProjectsStatus};

class SlackManager
{
    /** @var Client */
    private $apiClient;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var string */
    private $iconUrl;
    /** @var string */
    private $frontUrl;
    /** @var string */
    private $backUrl;
    /** @var string */
    private $environment;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Client                 $apiClient
     * @param EntityManagerInterface $entityManager
     * @param Packages               $assetsPackages
     * @param string                 $frontUrl
     * @param string                 $adminUrl
     * @param string                 $environment
     * @param LoggerInterface        $logger
     */
    public function __construct(
        Client $apiClient,
        EntityManagerInterface $entityManager,
        Packages $assetsPackages,
        string $frontUrl,
        string $adminUrl,
        string $environment,
        LoggerInterface $logger
    )
    {
        $this->apiClient     = $apiClient;
        $this->entityManager = $entityManager;
        $this->iconUrl       = $assetsPackages->getUrl('/assets/images/slack/unilend.png');
        $this->frontUrl      = $frontUrl;
        $this->backUrl       = $adminUrl;
        $this->environment   = $environment;
        $this->logger        = $logger;
    }

    /**
     * @param string      $message
     * @param string|null $channel
     *
     * @return bool
     */
    public function sendMessage(string $message, ?string $channel = null): bool
    {
        return true;

        try {
            $payload = $this->apiClient->createMessage();

            if (null !== $channel) {
                if ('prod' === $this->environment) {
                    $payload->setChannel($channel);
                } else {
                    $message = '[' . $channel . '] ' . $message;
                }
            }

            $payload->setText($message);
            $this->apiClient->sendMessage($payload);

            return true;
        } catch (Exception $exception) {
            $this->logger->error('Slack message could not be send: ' . $exception->getMessage() . ' - Message: ' . $message);

            return false;
        }
    }

    /**
     * @param Projects $project
     *
     * @return string
     */
    public function getProjectName(Projects $project)
    {
        $title   = $project->getTitle();
        $backUrl = $this->backUrl . '/dossiers/edit/' . $project->getIdProject();
        $company = $project->getIdCompany();

        if (empty($title) && $company) {
            $title = $company->getName();
        }

        if (empty($title) && $company) {
            $title = $company->getSiren();
        }

        if ($project->getStatus() >= ProjectsStatus::STATUS_ONLINE) {
            return '*<' . $this->frontUrl . '/projects/detail/' . $project->getSlug() . '|' . $title . '>* (<' . $backUrl . '|' . $project->getIdProject() . '>)';
        }

        return '*' . $title . '* (<' . $backUrl . '|' . $project->getIdProject() . '>)';
    }
}
