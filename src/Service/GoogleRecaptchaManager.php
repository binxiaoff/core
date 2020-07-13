<?php

declare(strict_types=1);

namespace Unilend\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class GoogleRecaptchaManager
{
    /** @var Client */
    private Client $client;
    /** @var string */
    private string $secret;
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var bool */
    private bool $debug;

    /**
     * @param Client          $client
     * @param string          $secret
     * @param LoggerInterface $logger
     * @param bool            $debug
     */
    public function __construct(Client $client, string $secret, LoggerInterface $logger, bool $debug = false)
    {
        $this->client = $client;
        $this->secret = $secret;
        $this->logger = $logger;
        $this->debug  = $debug;
    }

    /**
     * @param string|null $captchaResponse
     * @param string|null $remoteIp
     *
     * @throws JsonException
     *
     * @return bool
     */
    public function isValid(?string $captchaResponse, ?string $remoteIp = null): bool
    {
        // Condition to allow to test in development environment with postman without having to have a captcha token
        if ($this->debug) {
            return true;
        }

        if ($captchaResponse) {
            $response = $this->verify($captchaResponse, $remoteIp);

            if (200 !== $response->getStatusCode()) {
                $this->logger->warning('Unable to check Google reCAPTCHA - Invalid response code: ' . $response->getStatusCode());

                return true;
            }

            $content = $response->getBody()->getContents();
            $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (null === $content) {
                $this->logger->warning('Unable to check Google reCAPTCHA - JSON could not be decoded');

                return true;
            }

            if (false === isset($content['success'])) {
                $this->logger->warning('Unable to check Google reCAPTCHA - "success" key not found');

                return true;
            }

            return $content['success'];
        }

        return false;
    }

    /**
     * @param string      $captchaResponse
     * @param string|null $remoteIp
     *
     * @return ResponseInterface
     */
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
