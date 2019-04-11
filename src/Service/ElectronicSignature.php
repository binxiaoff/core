<?php declare(strict_types=1);

namespace Unilend\Service;

use DocuSign\eSign\{ApiClient, ApiException, Configuration};
use DocuSign\eSign\Api\{AuthenticationApi, AuthenticationApi\LoginOptions, EnvelopesApi};
use DocuSign\eSign\Model\{Document, EnvelopeDefinition, LoginAccount, LoginInformation, RecipientEmailNotification, Recipients, RecipientViewRequest, Signer, SignHere, Tabs};
use Lcobucci\JWT\{Builder, Signer\Rsa\Sha256};
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\{Request, RequestStack, Session\SessionInterface};
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Unilend\Entity\Clients;

// @todo webhooks: https://developers.docusign.com/esign-rest-api/code-examples/webhook-status
class ElectronicSignature
{
    const SESSION_TOKEN_KEY = 'DocuSignToken';

    const RECIPIENT_STATUS_DRAFT          = 'created';
    const RECIPIENT_STATUS_SENT           = 'sent';
    const RECIPIENT_STATUS_DELIVERED      = 'delivered';
    const RECIPIENT_STATUS_SIGNED         = 'signed';
    const RECIPIENT_STATUS_DECLINED       = 'declined';
    const RECIPIENT_STATUS_COMPLETED      = 'completed';
    const RECIPIENT_STATUS_FAX_PENDING    = 'faxpending';
    const RECIPIENT_STATUS_AUTO_RESPONDED = 'autoresponded';

    const ENVELOPE_STATUS_VOIDED     = 'voided';
    const ENVELOPE_STATUS_CREATED    = 'created';
    const ENVELOPE_STATUS_DELETED    = 'deleted';
    const ENVELOPE_STATUS_SENT       = 'sent';
    const ENVELOPE_STATUS_DELIVERED  = 'delivered';
    const ENVELOPE_STATUS_SIGNED     = 'signed';
    const ENVELOPE_STATUS_COMPLETED  = 'completed';
    const ENVELOPE_STATUS_DECLINED   = 'declined';
    const ENVELOPE_STATUS_TIMED_OUT  = 'timedOut';
    const ENVELOPE_STATUS_PROCESSING = 'processing';

    /** @var HttpClient */
    private $httpClient;
    /** @var string */
    private $integratorKey;
    /** @var string */
    private $userId;
    /** @var string */
    private $privateKey;
    /** @var string */
    private $accountHost;
    /** @var string */
    private $apiHost;
    /** @var RequestStack */
    private $requestStack;
    /** @var LoggerInterface */
    private $logger;
    /** @var null|string */
    private $testEmail;

    public function __construct(
        string $integratorKey,
        string $userId,
        string $privateKey,
        string $accountHost,
        string $apiHost,
        RequestStack $requestStack,
        LoggerInterface $logger,
        ?string $testEmail = null
    )
    {
        $this->httpClient    = HttpClient::create();
        $this->integratorKey = $integratorKey;
        $this->userId        = $userId;
        $this->privateKey    = $privateKey;
        $this->accountHost   = $accountHost;
        $this->apiHost       = $apiHost;
        $this->requestStack  = $requestStack;
        $this->logger        = $logger;
        $this->testEmail     = $testEmail;
    }

    /**
     * For demonstration purpose, only one document, one signer
     *
     * @param Clients $signerClient
     * @param string  $emailSubject
     * @param string  $documentName
     * @param string  $documentContent
     * @param string  $documentExtension
     * @param string  $signatureOffsetX
     * @param string  $signatureOffsetY
     * @param string  $returnUrl
     * @return string|null
     */
    public function createSignatureRequest(
        Clients $signerClient,
        string $emailSubject,
        string $documentName,
        string $documentContent,
        string $documentExtension,
        string $signatureOffsetX,
        string $signatureOffsetY,
        string $returnUrl
    ): ?string
    {
        try {
            $configuration = $this->getConfiguration();
            $loginAccount  = $this->getLoginAccount($configuration);

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
            $recipientNotification->setSupportedLanguage($signerClient->getIdLangue());

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
                ->setRecipientId($signerClient->getIdClient())
                ->setXPosition($signatureOffsetX)
                ->setYPosition($signatureOffsetY)
            ;

            $signatureTab = new Tabs();
            $signatureTab->setSignHereTabs([$signaturePosition]);

            $signer = new Signer();
            $signer
                ->setName($signerClient->getPrenom() . ' ' . $signerClient->getNom())
                ->setEmail($this->testEmail ?? $signerClient->getEmail())
                ->setRecipientId($signerClient->getIdClient())
                ->setClientUserId($signerClient->getIdClient()) // if set, email is not sent by DocuSign
                ->setEmailNotification($recipientNotification)
                ->setTabs($signatureTab)
                ->setRoutingOrder(1)
            ;

            $recipients = new Recipients();
            $recipients->setSigners([$signer]);

            $envelopeDefinition = new EnvelopeDefinition();
            $envelopeDefinition
                ->setEmailSubject($emailSubject)
                ->setDocuments([$document])
                ->setRecipients($recipients)
                ->setStatus(self::ENVELOPE_STATUS_SENT)
            ;

            $host = $loginAccount->getBaseUrl();
            $host = explode('/v2', $host);
            $host = $host[0];

            $configuration->setHost($host);

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
                ->setEmail($this->testEmail ?? $signer->getEmail())
            ;

            return $envelopeApi->createRecipientView(
                $loginAccount->getAccountId(),
                $envelope->getEnvelopeId(),
                $recipientViewRequest
            )->getUrl();
        } catch (ApiException $exception) {
            $this->logger->error('Unable to send electronic signature email with error: ' . $exception->getMessage(), [
                'response' => $exception->getResponseBody(),
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);
        }

        return null;
    }

    /**
     * @param Configuration $configuration
     * @return LoginAccount|null
     * @throws ApiException
     */
    private function getLoginAccount(Configuration $configuration): ?LoginAccount
    {
        $configuration->setHost($this->getApiEndpoint());

        $apiClient         = new ApiClient($configuration);
        $authenticationApi = new AuthenticationApi($apiClient);
        $options           = new LoginOptions();
        $loginInformation  = $authenticationApi->login($options);

        if ($loginInformation instanceof LoginInformation && count($loginInformation->getLoginAccounts()) > 0) {
            foreach ($loginInformation->getLoginAccounts() as $loginAccount) {
                if ('true' === $loginAccount['is_default']) {
                    return $loginAccount;
                }
            }
        }

        return null;
    }

    private function getConfiguration(): Configuration
    {
        $accessToken   = $this->getAccessToken();
        $configuration = new Configuration();
        $configuration
            ->addDefaultHeader('Authorization', 'Bearer ' . $accessToken)
            ->setAccessToken($accessToken)
            ->setHost($this->getAccountEndpoint())
        ;

        return $configuration;
    }

    private function getAccessToken(): ?string
    {
        $session      = $this->getSession();
        $sessionToken = $session->get(self::SESSION_TOKEN_KEY);

        if (null !== $sessionToken && $sessionToken['expiration'] > time()) {
            return $sessionToken['token'];
        }

        $signer  = new Sha256();
        $builder = new Builder();
        $token   = $builder
            ->issuedBy($this->integratorKey)
            ->with('sub', $this->userId)
            ->issuedAt(time())
            ->expiresAt(time() + 3600)
            ->canOnlyBeUsedBy($this->accountHost)
            ->with('scope', 'signature impersonation')
            ->sign($signer, $this->privateKey)
            ->getToken()
        ;

        try {
            $response = $this->httpClient->request(Request::METHOD_POST, $this->getAccountEndpoint(), [
                'body' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => (string)$token
                ]
            ]);

            $accessTokenResponse = json_decode($response->getContent());

            $session->set(self::SESSION_TOKEN_KEY, [
                'token'      => $accessTokenResponse->access_token,
                'expiration' => time() + $accessTokenResponse->expires_in
            ]);

            return $accessTokenResponse->access_token;
        } catch (ExceptionInterface $exception) {
            // @todo
        }

        return null;
    }

    private function getAccountEndpoint(): string
    {
        return 'https://' . $this->accountHost . '/oauth/token';
    }

    private function getApiEndpoint(): string
    {
        return 'https://' . $this->apiHost . '/restapi';
    }

    private function getSession(): ?SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request->getSession();
    }
}
