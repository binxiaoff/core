<?php

declare(strict_types=1);

namespace KLS\Core\Service;

use DocuSign\eSign\Api\AuthenticationApi;
use DocuSign\eSign\Api\AuthenticationApi\LoginOptions;
use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\ApiClient;
use DocuSign\eSign\ApiException;
use DocuSign\eSign\Configuration;
use DocuSign\eSign\Model\Document;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\LoginAccount;
use DocuSign\eSign\Model\LoginInformation;
use DocuSign\eSign\Model\RecipientEmailNotification;
use DocuSign\eSign\Model\Recipients;
use DocuSign\eSign\Model\RecipientViewRequest;
use DocuSign\eSign\Model\Signer;
use DocuSign\eSign\Model\SignHere;
use DocuSign\eSign\Model\Tabs;
use KLS\Core\Entity\User;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @todo webhooks: https://developers.docusign.com/esign-rest-api/code-examples/webhook-status
 */
class ElectronicSignatureManager
{
    public const SESSION_TOKEN_KEY = 'DocuSignToken';

    public const RECIPIENT_STATUS_DRAFT          = 'created';
    public const RECIPIENT_STATUS_SENT           = 'sent';
    public const RECIPIENT_STATUS_DELIVERED      = 'delivered';
    public const RECIPIENT_STATUS_SIGNED         = 'signed';
    public const RECIPIENT_STATUS_DECLINED       = 'declined';
    public const RECIPIENT_STATUS_COMPLETED      = 'completed';
    public const RECIPIENT_STATUS_FAX_PENDING    = 'faxpending';
    public const RECIPIENT_STATUS_AUTO_RESPONDED = 'autoresponded';

    public const ENVELOPE_STATUS_VOIDED     = 'voided';
    public const ENVELOPE_STATUS_CREATED    = 'created';
    public const ENVELOPE_STATUS_DELETED    = 'deleted';
    public const ENVELOPE_STATUS_SENT       = 'sent';
    public const ENVELOPE_STATUS_DELIVERED  = 'delivered';
    public const ENVELOPE_STATUS_SIGNED     = 'signed';
    public const ENVELOPE_STATUS_COMPLETED  = 'completed';
    public const ENVELOPE_STATUS_DECLINED   = 'declined';
    public const ENVELOPE_STATUS_TIMED_OUT  = 'timedOut';
    public const ENVELOPE_STATUS_PROCESSING = 'processing';

    public const RECIPIENT_ACTION_CANCEL           = 'cancel';
    public const RECIPIENT_ACTION_DECLINE          = 'decline';
    public const RECIPIENT_ACTION_EXCEPTION        = 'exception';
    public const RECIPIENT_ACTION_FAX_PENDING      = 'fax_pending';
    public const RECIPIENT_ACTION_ID_CHECK_FAILED  = 'id_check_failed';
    public const RECIPIENT_ACTION_SESSION_TIMEOUT  = 'session_timeout';
    public const RECIPIENT_ACTION_SIGNING_COMPLETE = 'signing_complete';
    public const RECIPIENT_ACTION_TTL_EXPIRED      = 'ttl_expired';
    public const RECIPIENT_ACTION_VIEWING_COMPLETE = 'viewing_complete';

    /** @var string */
    private $integratorKey;
    /** @var string */
    private $userId;
    /** @var string */
    private $privateKey;
    /** @var HttpClientInterface */
    private $httpClient;
    /** @var string */
    private $accountHost;
    /** @var string */
    private $apiHost;
    /** @var RequestStack */
    private $requestStack;
    /** @var LoggerInterface */
    private $logger;
    /** @var bool */
    private $debug;

    public function __construct(
        string $integratorKey,
        string $userId,
        string $privateKey,
        HttpClientInterface $httpClient,
        string $accountHost,
        string $apiHost,
        RequestStack $requestStack,
        LoggerInterface $logger,
        bool $debug
    ) {
        $this->integratorKey = $integratorKey;
        $this->userId        = $userId;
        $this->privateKey    = $privateKey;
        $this->httpClient    = $httpClient;
        $this->accountHost   = $accountHost;
        $this->apiHost       = $apiHost;
        $this->requestStack  = $requestStack;
        $this->logger        = $logger;
        $this->debug         = $debug;
    }

    /**
     * For demonstration purpose, only one document, one signer.
     */
    public function createSignatureRequest(
        User $signerClient,
        string $emailSubject,
        string $documentName,
        string $documentContent,
        string $documentExtension,
        string $signatureOffsetX,
        string $signatureOffsetY,
        string $returnUrl
    ): ?array {
        try {
            $loginAccount = $this->getLoginAccount();

            if (null === $loginAccount) {
                return null;
            }

            $document = new Document();
            $document
                ->setDocumentId(1)
                ->setName($documentName)
                ->setDocumentBase64($documentContent)
                ->setFileExtension($documentExtension)
            ;

            $recipientNotification = new RecipientEmailNotification();
            $recipientNotification->setSupportedLanguage($signerClient->getIdLanguage());

            // Example of signature with auto placement (https://developers.docusign.com/esign-rest-api/guides/concepts/tabs#anchoring-tabs)
//            $signaturePosition = new SignHere();
//            $signaturePosition
//                ->setAnchorString('[SIGNATURE ANCHOR LABEL]')
//                ->setAnchorUnits('pixels')
//                ->setAnchorXOffset(10)
//                ->setAnchorYOffset(50);

            $signaturePosition = new SignHere();
            $signaturePosition
                ->setDocumentId(1)
                ->setPageNumber(1)
                ->setTabLabel('SignHereTab')
                ->setRecipientId($signerClient->getId())
                ->setXPosition($signatureOffsetX)
                ->setYPosition($signatureOffsetY)
            ;

            $signatureTab = new Tabs();
            $signatureTab->setSignHereTabs([$signaturePosition]);

            $signer = new Signer();
            $signer
                ->setName($signerClient->getFirstName() . ' ' . $signerClient->getLastName())
                ->setEmail($signerClient->getEmail())
                ->setRecipientId($signerClient->getId())
                ->setClientUserId($signerClient->getId()) // if set, email is not sent by DocuSign
                ->setEmailNotification($recipientNotification)
                ->setTabs($signatureTab)
                ->setRoutingOrder(1)
            ;

            $recipients = new Recipients();
            $recipients->setSigners([$signer]);

            if ($this->debug) {
                $emailSubject = '[DEMO] ' . $emailSubject;
            }

            $envelopeDefinition = new EnvelopeDefinition();
            $envelopeDefinition
                ->setEmailSubject($emailSubject)
                ->setDocuments([$document])
                ->setRecipients($recipients)
                ->setStatus(self::ENVELOPE_STATUS_SENT)
            ;

            $host = $loginAccount->getBaseUrl();
            $host = \explode('/v2', $host);
            $host = $host[0];

            $configuration = $this->createConfiguration($host);

            $apiClient   = new ApiClient($configuration);
            $envelopeApi = new EnvelopesApi($apiClient);
            $envelope    = $envelopeApi->createEnvelope($loginAccount->getAccountId(), $envelopeDefinition);

            // Get signature link - Lifetime 5 minutes (https://developers.docusign.com/esign-rest-api/guides/features/embedding)
            $recipientViewRequest = new RecipientViewRequest();
            $recipientViewRequest
                ->setAuthenticationMethod('None')
                ->setReturnUrl($returnUrl)
                ->setRecipientId($signer->getRecipientId())
                ->setClientUserId($signer->getClientUserId())
                ->setUserName($signer->getName())
                ->setEmail($signer->getEmail())
            ;

            $viewUrl = $envelopeApi->createRecipientView(
                $loginAccount->getAccountId(),
                $envelope->getEnvelopeId(),
                $recipientViewRequest
            );

            return [
                'envelope' => $envelope->getEnvelopeId(),
                'url'      => $viewUrl->getUrl(),
            ];
        } catch (ApiException $exception) {
            $this->logger->error('Unable to send electronic signature email with error: ' . $exception->getMessage(), [
                'response' => $exception->getResponseBody(),
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
            ]);
        }

        return null;
    }

    /**
     * @throws ApiException
     */
    private function getLoginAccount(): ?LoginAccount
    {
        $configuration = $this->createConfiguration($this->getLoginAccountEndpoint());

        $apiClient         = new ApiClient($configuration);
        $authenticationApi = new AuthenticationApi($apiClient);
        $options           = new LoginOptions();
        $loginInformation  = $authenticationApi->login($options);

        if ($loginInformation instanceof LoginInformation && \count($loginInformation->getLoginAccounts()) > 0) {
            foreach ($loginInformation->getLoginAccounts() as $loginAccount) {
                if ('true' === $loginAccount['is_default']) {
                    return $loginAccount;
                }
            }
        }

        return null;
    }

    private function createConfiguration(string $host): Configuration
    {
        $accessToken   = $this->createAccessToken();
        $configuration = new Configuration();
        $configuration
            ->addDefaultHeader('Authorization', 'Bearer ' . $accessToken)
            ->setAccessToken($accessToken)
            ->setHost($host)
        ;

        return $configuration;
    }

    private function createAccessToken(): ?string
    {
        $session      = $this->getSession();
        $sessionToken = $session->get(self::SESSION_TOKEN_KEY);

        if (null !== $sessionToken && $sessionToken['expiration'] > \time()) {
            return $sessionToken['token'];
        }

        $signer  = new Sha256();
        $builder = new Builder();
        $token   = $builder
            ->issuedBy($this->integratorKey)
            ->withClaim('sub', $this->userId)
            ->issuedAt(\time())
            ->expiresAt(\time() + 3600)
            ->permittedFor($this->accountHost)
            ->withClaim('scope', 'signature impersonation')
            ->getToken($signer, new Key($this->privateKey))
        ;

        try {
            $response = $this->httpClient->request(Request::METHOD_POST, $this->getOAuthEndpoint(), [
                'body' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => (string) $token,
                ],
            ]);

            $accessTokenResponse = \json_decode($response->getContent());

            $session->set(self::SESSION_TOKEN_KEY, [
                'token'      => $accessTokenResponse->access_token,
                'expiration' => \time() + $accessTokenResponse->expires_in,
            ]);

            return $accessTokenResponse->access_token;
        } catch (ExceptionInterface $exception) {
            // @todo
        }

        return null;
    }

    private function getOAuthEndpoint(): string
    {
        return 'https://' . $this->accountHost . '/oauth/token';
    }

    private function getLoginAccountEndpoint(): string
    {
        return 'https://' . $this->apiHost . '/restapi';
    }

    private function getSession(): ?SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request->getSession();
    }
}
