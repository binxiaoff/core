<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class GoogleRecaptchaManager
{
    const FORM_FIELD_NAME = 'g-recaptcha-response';

    const ERROR_CODE_MISSING_SECRET        = 'missing-input-secret';
    const ERROR_CODE_INVALID_SECRET        = 'invalid-input-secret';
    const ERROR_CODE_MISSING_RESPONSE      = 'missing-input-response';
    const ERROR_CODE_INVALID_RESPONSE      = 'invalid-input-response';
    const ERROR_CODE_BAD_REQUEST           = 'bad-request';
    const ERROR_CODE_BAD_TIMEOUT_DUPLICATE = 'timeout-or-duplicate';

    /** @var Client */
    private $client;
    /** @var string */
    private $secret;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Client          $client
     * @param string          $secret
     * @param LoggerInterface $logger
     */
    public function __construct(Client $client, string $secret, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->secret = $secret;
        $this->logger = $logger;
    }

    /**
     * @param string      $captchaResponse
     * @param string|null $remoteIp
     *
     * @return ResponseInterface
     */
    public function verify(string $captchaResponse, ?string $remoteIp = null): ResponseInterface
    {
        $response = $this->client->post('', [
            RequestOptions::FORM_PARAMS => [
                'secret'   => $this->secret,
                'response' => $captchaResponse,
                'remoteip' => $remoteIp
            ]
        ]);

        return $response;
    }

    /**
     * @param string      $captchaResponse
     * @param string|null $remoteIp
     *
     * @return bool
     */
    public function isValid(string $captchaResponse, ?string $remoteIp = null): bool
    {
        $response = $this->verify($captchaResponse, $remoteIp);

        if (200 !== $response->getStatusCode()) {
            $this->logger->warning('Unable to check Google reCAPTCHA - Invalid response code: ' . $response->getStatusCode());
            return true;
        }

        $content = $response->getBody()->getContents();
        $content = json_decode($content, true);

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
}
