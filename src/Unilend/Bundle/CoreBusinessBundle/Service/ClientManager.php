<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class ClientManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var ClientSettingsManager */
    private $clientSettingsManager;
    /** @var TermsOfSaleManager */
    private $termsOfSaleManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManager         $entityManager
     * @param ClientSettingsManager $clientSettingsManager
     * @param TermsOfSaleManager    $termsOfSaleManager
     * @param LoggerInterface       $logger
     */
    public function __construct(
        EntityManager $entityManager,
        ClientSettingsManager $clientSettingsManager,
        TermsOfSaleManager $termsOfSaleManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager         = $entityManager;
        $this->clientSettingsManager = $clientSettingsManager;
        $this->termsOfSaleManager    = $termsOfSaleManager;
        $this->logger                = $logger;
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function isBetaTester(Clients $client): bool
    {
        try {
            return (bool) $this->clientSettingsManager->getSetting($client, \client_setting_type::TYPE_BETA_TESTER);
        } catch (InvalidArgumentException $exception) {
            $this->logger->warning(
                'Invalid argument exception while retrieving beta tester status: ' . $exception->getMessage(),
                ['id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
            return false;
        }
    }

    /**
     * @param Clients $client
     *
     * @return string
     */
    public function getInitials(Clients $client): string
    {
        $initials = substr($client->getPrenom(), 0, 1) . substr($client->getNom(), 0, 1);
        //TODO decide which initials to use in case of company

        return $initials;
    }
}
