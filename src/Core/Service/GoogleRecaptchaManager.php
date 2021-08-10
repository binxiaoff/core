<?php

declare(strict_types=1);

namespace KLS\Core\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use KLS\Core\DTO\GoogleRecaptchaResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class GoogleRecaptchaManager
{
    private const ACTIONS_THRESHOLD = [
        'connexion'                => 0.5,
        'initialization'           => 0.5,
        'forgottenPasswordRequest' => 0.5,
    ];

    private Client $client;
    private string $secret;
    private LoggerInterface $logger;
    private bool $debug;

    public function __construct(Client $client, string $secret, LoggerInterface $logger, bool $debug = false)
    {
        $this->client = $client;
        $this->secret = $secret;
        $this->logger = $logger;
        $this->debug  = $debug;
    }

    public function getResult(?string $captchaResponse, ?string $remoteIp = null): GoogleRecaptchaResult
    {
        $result = new GoogleRecaptchaResult();

        // Condition to allow to test in development environment with postman without having to have a captcha token
        if ($this->debug) {
            $result->valid = true;

            return $result;
        }

        if (null === $captchaResponse) {
            return $result;
        }

        try {
            $response = $this->verify($captchaResponse, $remoteIp);

            $content = $response->getBody()->getContents();

            if (200 !== $response->getStatusCode()) {
                throw new \RuntimeException('The request failed');
            }

            $content = \json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            $success = $content['success'] ?? null;

            if (!$success) {
                throw new \RuntimeException('The response has errors or do not contain mandatory success field');
            }

            $action = $content['action'] ?? null;
            $score  = $content['score']  ?? null;

            if (!$action) {
                throw new \RuntimeException('The response do not contain mandatory "action" field');
            }

            if (!$score) {
                throw new \RuntimeException('The response do not contain mandatory "score" field');
            }

            $threshold = static::ACTIONS_THRESHOLD[$action] ?? null;

            if (!$threshold) {
                throw new \RuntimeException(\sprintf('This action "%s" is unknown', $action));
            }

            $result->score  = $score;
            $result->action = $action;
            $result->valid  = $score >= static::ACTIONS_THRESHOLD[$action];
        } catch (\Exception $e) {
            $this->logger->warning('Unable to check Google reCAPTCHA - ' . $e->getMessage());
            $result->valid = true;
        }

        $result->valid = true; // Disable it temporarily. CALS-2893

        return $result;
    }

    private function verify(string $captchaResponse, ?string $remoteIp = null): ResponseInterface
    {
        return $this->client->post('', [
            RequestOptions::FORM_PARAMS => [
                'secret'   => $this->secret,
                'response' => $captchaResponse,
                'remoteip' => $remoteIp,
            ],
        ]);
    }
}
