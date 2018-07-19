<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, ClientSettingType
};

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
        $setting = $this->clientSettingsManager->getSetting($client, ClientSettingType::TYPE_BETA_TESTER);

        if (null === $setting) {
            $this->logger->warning('Unable to retrieve client beta tester status: ' . $client->getIdClient(), [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__
            ]);

            return false;
        }

        return (bool) $setting;
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
