<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Unilend\Bundle\WSClientBundle\Entity\Codinf\PaymentIncident;

class CodinfManager
{
    /** @var  Client */
    private $client;
    /** @var string */
    private $user;
    /** @var string */
    private $password;
    /** @var  LoggerInterface */
    private $logger;
    /** @var CallHistoryManager */
    private $callHistoryManager;
    /** @var Serializer */
    private $serializer;

    /**
     * CodinfManager constructor.
     *
     * @param ClientInterface $client
     * @param $user
     * @param $password
     * @param $baseUrl
     * @param LoggerInterface $logger
     * @param CallHistoryManager $callHistoryManager
     * @param Serializer $serializer
     */
    public function __construct(ClientInterface $client, $user, $password, $baseUrl, LoggerInterface $logger, CallHistoryManager $callHistoryManager, Serializer $serializer)
    {
        $this->client             = $client;
        $this->user               = $user;
        $this->password           = $password;
        $this->logger             = $logger;
        $this->callHistoryManager = $callHistoryManager;
        $this->serializer         = $serializer;
    }

    /**
     * @param $siren
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param bool $includeRegularized
     * @return null|PaymentIncident
     */
    public function getIncidentList($siren, \DateTime $startDate = null, \DateTime $endDate = null, $includeRegularized = false)
    {
        if (true === empty($siren)) {
            throw new \InvalidArgumentException('siren is missing');
        }

        $query['siren']      = $siren;
        $query['allcomites'] = 1;
        $query['usr']        = $this->user;
        $query['pswd']       = $this->password;

        if (null !== $startDate && $startDate instanceof \DateTime) {
            $query['dd'] = '2010-01-01';
            $query['dd'] = $startDate->format('Y-m-d');
        }

        if (null !== $endDate && $endDate instanceof \DateTime) {
            $query['df'] = $endDate->format('Y-m-d');
        }

        if (false !== $includeRegularized) {
            $query['inc_reg'] = 1;
        }

        $response   = $this->client->get(
            'get_list_v2.php',
            [
                'query'    => $query,
                'on_stats' => $this->callHistoryManager->addResourceCallHistoryLog('codinf', __FUNCTION__, 'GET', $siren)
            ]
        );
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren];
        $incidents  = $response->getBody()->getContents();
        $this->logger->info('Call to get_list_v2. Response: ' . $incidents, $logContext);

        try {
            return $this->serializer->deserialize($incidents, PaymentIncident::class, 'xml');
        } catch (\Exception $exception) {
            $this->logger->error('Could not deserialize response: ' . $exception->getMessage() . ' siren: ' . $siren, $logContext);

            return null;
        }
    }
}
