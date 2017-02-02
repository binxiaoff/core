<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use CL\Slack\Payload\ChatPostMessagePayload;
use CL\Slack\Payload\PayloadResponseInterface;
use CL\Slack\Transport\ApiClient;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;

class SlackManager
{
    /** @var ApiClient */
    private $apiClient;
    /** @var RouterInterface */
    private $router;
    /** @var string */
    private $iconUrl;
    /** @var string */
    private $defaultChannel;

    /**
     * @param ApiClient $apiClient
     * @param Packages  $assetsPackages
     * @param string    $defaultChannel
     */
    public function __construct(ApiClient $apiClient, RouterInterface $router, Packages $assetsPackages, $defaultChannel)
    {
        $this->apiClient      = $apiClient;
        $this->router         = $router;
        $this->iconUrl        = $assetsPackages->getUrl('/assets/images/slack/unilend.png');
        $this->defaultChannel = $defaultChannel;
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

        return $this->apiClient->send($payload);
    }

    /**
     * @param \projects $project
     * @return string
     */
    public function getProjectLink(\projects $project)
    {
        return '*<' . $this->router->generate('project_detail', ['projectSlug' => $project->slug], RouterInterface::ABSOLUTE_URL) . '|' . $project->title . '>*';
    }
}
