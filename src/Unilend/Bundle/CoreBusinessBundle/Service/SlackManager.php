<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Http\Client\Exception;
use Nexy\Slack\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Projects, ProjectsStatus};

class SlackManager
{
    /** @var Client */
    private $apiClient;
    /** @var EntityManager */
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
     * @param Client          $apiClient
     * @param EntityManager   $entityManager
     * @param Packages        $assetsPackages
     * @param string          $scheme
     * @param string          $frontHost
     * @param string          $backHost
     * @param string          $environment
     * @param LoggerInterface $logger
     */
    public function __construct(
        Client $apiClient,
        EntityManager $entityManager,
        Packages $assetsPackages,
        string $scheme,
        string $frontHost,
        string $backHost,
        string $environment,
        LoggerInterface $logger
    )
    {
        $this->apiClient     = $apiClient;
        $this->entityManager = $entityManager;
        $this->iconUrl       = $assetsPackages->getUrl('/assets/images/slack/unilend.png');
        $this->frontUrl      = $scheme . '://' . $frontHost;
        $this->backUrl       = $scheme . '://' . $backHost;
        $this->environment   = $environment;
        $this->logger        = $logger;
    }

    /**
     * @param string      $message
     * @param string|null $channel
     *
     * @return bool
     */
    public function sendMessage($message, $channel = null): bool
    {
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

        if ($project->getStatus() >= ProjectsStatus::EN_FUNDING) {
            return '*<' . $this->frontUrl . '/projects/detail/' . $project->getSlug() . '|' . $title . '>* (<' . $backUrl . '|' . $project->getIdProject() . '>)';
        }

        return '*' . $title . '* (<' . $backUrl . '|' . $project->getIdProject() . '>)';
    }
}
