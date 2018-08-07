<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use JMS\Serializer\Serializer;
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

    /** @var Client */
    private $client;
    /** @var LoggerInterface */
    private $logger;
    /** @var ResourceManager */
    private $resourceManager;
    /** @var EntityManager */
    private $entityManager;
    /** @var string */
    private $login;
    /** @var string */
    private $password;
    /** @var Serializer */
    private $serializer;

    /**
     * @param Client          $client
     * @param LoggerInterface $logger
     * @param ResourceManager $resourceManager
     * @param EntityManager   $entityManager
     * @param string          $login
     * @param string          $password
     * @param Serializer      $serializer
     */
    public function __construct(
        Client $client,
        LoggerInterface $logger,
        ResourceManager $resourceManager,
        EntityManager $entityManager,
        string $login,
        string $password,
        Serializer $serializer
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
     * @param array $data
     *
     * @return Identity
     */
    public function checkIdentity(array $data): Identity
    {
        $response = $this->postMultipart(self::RESOURCE_CHECK_IDENTITY, $data);

        $identity = $this->serializer->deserialize(json_encode($response->resource), Identity::class, 'json');

        return $identity;
    }

    /**
     * @param array $data
     *
     * @return Rib
     */
    public function checkIban(array $data): Rib
    {
        $response = $this->postMultipart(self::RESOURCE_CHECK_IBAN, $data);

        $rib = $this->serializer->deserialize(json_encode($response->resource), Rib::class, 'json');

        return $rib;
    }

    /**
     * @param array $data
     *
     * @return HousingCertificate
     */
    public function checkAddress(array $data): HousingCertificate
    {
        $response = $this->postMultipart(self::RESOURCE_CHECK_ADDRESS, $data);

        $housingCertificate = $this->serializer->deserialize(json_encode($response->resource), HousingCertificate::class, 'json');

        return $housingCertificate;
    }

    public function getClientKYCStatus(Clients $client): Kyc
    {
        $response = $this->getKyc($client);

        $kycInfo = $this->serializer->deserialize(json_encode($response->resource), Kyc::class, 'json');

        return $kycInfo;
    }



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


    private function getKyc(Clients $client)
    {
        $wsResource = $this->resourceManager->getResource(self::RESOURCE_CHECK_KYC);

        $response = $this->client->put(
            $wsResource->getResourceName() . '/' . $client->getIdClient(), [
                                              'auth'    => [$this->login, $this->password, 'basic'],
                                              'dossier' => $client->getIdClient()
                                          ]
        );

        return $this->handleResponse($response);
    }


    private function handleResponse(ResponseInterface $response)
    {
        if (200 === $response->getStatusCode()){
            $stream = $response->getBody();
            $stream->rewind();
            $content = json_decode($stream->getContents());

            return $content;
        }


    }

    private function formatDataForMultipart($data)
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
