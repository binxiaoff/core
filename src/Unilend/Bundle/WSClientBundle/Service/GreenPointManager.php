<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\WSClientBundle\Entity\GreenPoint\{HousingCertificate, Identity, Kyc, Rib};

class GreenPointManager
{
    const RESOURCE_CHECK_IDENTITY = 'check_identity';
    const RESOURCE_CHECK_IBAN     = 'check_iban';
    const RESOURCE_CHECK_ADDRESS  = 'check_address';
    const RESOURCE_CHECK_KYC      = 'check_kyc';

    const TYPE_IDENTITY_DOCUMENT   = 1;
    const TYPE_RIB                 = 2;
    const TYPE_HOUSING_CERTIFICATE = 3;

    const DETAIL_TRUE  = 1;
    const DETAIL_FALSE = 0;

    const ASYNCHRONOUS_SUCCESS = 1;

    const NOT_VERIFIED                   = 0;
    const OUT_OF_BOUNDS                  = 1;
    const FALSIFIED_OR_MINOR             = 2;
    const ILLEGIBLE                      = 3;
    const VERSO_MISSING                  = 4;
    const NAME_SURNAME_INVERSION         = 5;
    const INCOHERENT_OTHER_ERROR         = 6;
    const EXPIRED                        = 7;
    const CONFORM_COHERENT_NOT_QUALIFIED = 8;
    const CONFORM_COHERENT_QUALIFIED     = 9;

    const SUCCESS_HTTP_STATUS = [200, 201, 202, 204];

    /** @var Client */
    private $client;
    /** @var LoggerInterface */
    private $logger;
    /** @var ResourceManager */
    private $resourceManager;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var string */
    private $login;
    /** @var string */
    private $password;
    /** @var SerializerInterface */
    private $serializer;

    /**
     * @param Client                 $client
     * @param LoggerInterface        $logger
     * @param ResourceManager        $resourceManager
     * @param EntityManagerInterface $entityManager
     * @param string                 $login
     * @param string                 $password
     * @param SerializerInterface    $serializer
     */
    public function __construct(
        Client $client,
        LoggerInterface $logger,
        ResourceManager $resourceManager,
        EntityManagerInterface $entityManager,
        string $login,
        string $password,
        SerializerInterface $serializer
    )
    {
        $this->client          = $client;
        $this->logger          = $logger;
        $this->resourceManager = $resourceManager;
        $this->entityManager   = $entityManager;
        $this->login           = $login;
        $this->password        = $password;
        $this->serializer      = $serializer;
    }

    /**
     * @param $data
     *
     * @return Identity
     * @throws \Exception
     */
    public function checkIdentity($data): Identity
    {
        $response = $this->postMultipart(self::RESOURCE_CHECK_IDENTITY, $data);
        $identity = $this->serializer->deserialize(json_encode($response->resource), Identity::class, 'json');

        return $identity;
    }

    /**
     * @param mixed $data
     *
     * @return Rib
     * @throws \Exception
     */
    public function checkIban($data): Rib
    {
        $response = $this->postMultipart(self::RESOURCE_CHECK_IBAN, $data);
        $rib      = $this->serializer->deserialize(json_encode($response->resource), Rib::class, 'json');

        return $rib;
    }

    /**
     * @param mixed $data
     *
     * @return HousingCertificate
     * @throws \Exception
     */
    public function checkAddress($data): HousingCertificate
    {
        $response           = $this->postMultipart(self::RESOURCE_CHECK_ADDRESS, $data);
        $housingCertificate = $this->serializer->deserialize(json_encode($response->resource), HousingCertificate::class, 'json');

        return $housingCertificate;
    }

    /**
     * @param Clients $client
     *
     * @return Kyc
     * @throws \Exception
     */
    public function getClientKYCStatus(Clients $client): Kyc
    {
        $response = $this->getKyc($client);
        $kycInfo  = $this->serializer->deserialize(json_encode($response->resource), Kyc::class, 'json');

        return $kycInfo;
    }

    /**
     * @param string $resourceLabel
     * @param array  $data
     *
     * @return mixed
     * @throws \Exception
     */
    private function postMultipart(string $resourceLabel, array $data)
    {
        if (self::RESOURCE_CHECK_KYC !== $resourceLabel && false === array_key_exists('files', $data)) {
            throw new \InvalidArgumentException('Data for GreenPoint should contain files');
        }

        $wsResource = $this->resourceManager->getResource($resourceLabel);
        if (null === $wsResource) {
            throw new \InvalidArgumentException('The GreenPoint ressource type is not supported');
        }

        $response = $this->client->post(
            $wsResource->getResourceName(), [
                'auth'      => [$this->login, $this->password, 'basic'],
                'multipart' => $this->formatDataForMultipart($data)
            ]
        );

        return $this->handleResponse($response);
    }

    /**
     * @param Clients $client
     *
     * @return mixed
     * @throws \Exception
     */
    private function getKyc(Clients $client)
    {
        $wsResource = $this->resourceManager->getResource(self::RESOURCE_CHECK_KYC);

        $response = $this->client->get(
            $wsResource->getResourceName() . '/' . $client->getIdClient(), [
                'auth'    => [$this->login, $this->password, 'basic'],
                'dossier' => $client->getIdClient()
            ]
        );

        return $this->handleResponse($response);
    }

    /**
     * @param ResponseInterface $response
     *
     * @return mixed
     * @throws \Exception
     */
    private function handleResponse(ResponseInterface $response)
    {
        if (false === in_array($response->getStatusCode(), self::SUCCESS_HTTP_STATUS)) {
            throw new \Exception('GreenPoint returned unexpected response. statusCode: ' . $response->getStatusCode() . ' reasonPhrase: ' . $response->getReasonPhrase());
        }

        $stream = $response->getBody();
        $stream->rewind();
        $content = json_decode($stream->getContents());

        return $content;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function formatDataForMultipart(array $data): array
    {
        $multipart = [];
        foreach ($data as $name => $content) {
            $multipart[] = [
                'name'     => $name,
                'contents' => $content
            ];
        }

        return $multipart;
    }
}
