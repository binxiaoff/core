<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use CL\Slack\Payload\ChatPostMessagePayload;
use CL\Slack\Payload\PayloadResponseInterface;
use CL\Slack\Transport\ApiClientInterface;
use Symfony\Component\Asset\Packages;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class SlackManager
{
    /** @var ApiClientInterface */
    private $apiClient;
    /** @var EntityManager */
    private $entityManager;
    /** @var string */
    private $iconUrl;
    /** @var string*/
    private $frontUrl;
    /** @var string */
    private $environment;

    /**
     * @param ApiClientInterface $apiClient
     * @param Packages           $assetsPackages
     * @param string             $defaultChannel
     * @param string             $environment
     */
    public function __construct(
        ApiClientInterface $apiClient,
        EntityManager $entityManager,
        Packages $assetsPackages,
        $defaultChannel,
        $environment
    )
    {
        $this->apiClient      = $apiClient;
        $this->entityManager  = $entityManager;
        $this->iconUrl        = $assetsPackages->getUrl('/assets/images/slack/unilend.png');
        $this->frontUrl       = $assetsPackages->getUrl('');
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
     * @param \projects $project
     * @return string
     */
    public function getProjectName(\projects $project)
    {
        $title = $project->title;

        if (empty($title)) {
            /** @var \companies $company */
            $company = $this->entityManager->getRepository('companies');
            $company->get($project->id_company);

            $title = $company->name;
        }

        if ($project->status >= \projects_status::EN_FUNDING) {
            return '*<' . $this->frontUrl . '/projects/detail/' . $project->slug . '|' . $title . '>*';
        }

        return '*' . $title . '*';
    }
}
