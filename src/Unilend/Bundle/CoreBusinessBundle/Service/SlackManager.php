<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use CL\Slack\Payload\ChatPostMessagePayload;
use CL\Slack\Payload\PayloadResponseInterface;
use CL\Slack\Transport\ApiClientInterface;
use Symfony\Component\Asset\Packages;
use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;

class SlackManager
{
    /** @var ApiClientInterface */
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

    /**
     * @param ApiClientInterface $apiClient
     * @param EntityManager      $entityManager
     * @param Packages           $assetsPackages
     * @param string             $scheme
     * @param string             $frontHost
     * @param string             $backHost
     * @param string             $defaultChannel
     * @param string             $environment
     */
    public function __construct(
        ApiClientInterface $apiClient,
        EntityManager $entityManager,
        Packages $assetsPackages,
        $scheme,
        $frontHost,
        $backHost,
        $defaultChannel,
        $environment
    )
    {
        $this->apiClient      = $apiClient;
        $this->entityManager  = $entityManager;
        $this->iconUrl        = $assetsPackages->getUrl('/assets/images/slack/unilend.png');
        $this->frontUrl       = $scheme . '://' . $frontHost;
        $this->backUrl        = $scheme . '://' . $backHost;
        $this->defaultChannel = $defaultChannel;
        $this->environment    = $environment;
    }

    /**
     * @param string      $message
     * @param string|null $channel
     * @return PayloadResponseInterface
     */
    public function sendMessage($message, $channel = null)
    {
        if (null === $channel) {
            $channel = $this->defaultChannel;
        }

        $payload = new ChatPostMessagePayload();
        $payload->setAsUser(false);
        $payload->setUsername('Unilend');
        $payload->setIconUrl($this->iconUrl);
        $payload->setChannel($channel);
        $payload->setText($message);

        if ('prod' !== $this->environment) {
            $payload->setChannel($this->defaultChannel);
        }

        return $this->apiClient->send($payload);
    }

    /**
     * @param \projects|Projects $project
     * @return string
     */
    public function getProjectName($project)
    {
        if ($project instanceof \projects) {
            $project = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);
        }

        $title   = $project->getTitle();
        $backUrl = $this->backUrl . '/dossiers/edit/' . $project->getIdProject();

        if (empty($title)) {
            $title = $project->getIdCompany()->getName();
        }

        if (empty($title)) {
            $title = $project->getIdCompany()->getSiren();
        }

        if ($project->getStatus() >= \projects_status::EN_FUNDING) {
            return '*<' . $this->frontUrl . '/projects/detail/' . $project->getSlug() . '|' . $title . '>* (<' . $backUrl . '|' . $project->getIdProject() . '>)';
        }

        return '*' . $title . '* (<' . $backUrl . '|' . $project->getIdProject() . '>)';
    }
}
