<?php

declare(strict_types=1);

namespace Unilend\Service;

use Http\Client\Exception;
use Nexy\Slack\Client;
use Psr\Log\LoggerInterface;

class SlackManager
{
    /** @var Client */
    private $apiClient;
    /** @var string */
    private $environment;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Client          $apiClient
     * @param string          $environment
     * @param LoggerInterface $logger
     */
    public function __construct(
        Client $apiClient,
        string $environment,
        LoggerInterface $logger
    ) {
        $this->apiClient   = $apiClient;
        $this->environment = $environment;
        $this->logger      = $logger;
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
}
