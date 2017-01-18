<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Unilend\Bundle\WSClientBundle\Entity\Codinf\IncidentList;

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
    private $monitoring;

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
     * @param boolean $activate
     */
    public function setMonitoring($activate)
    {
        $this->monitoring = $activate;
    }

    /**
     * @param $siren
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param bool $includeRegularized
     * @return null|IncidentList
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
            $query['dd'] = $startDate->format('Y-m-d');
        }

        if (null !== $endDate && $endDate instanceof \DateTime) {
            $query['df'] = $endDate->format('Y-m-d');
        }

        if (false !== $includeRegularized) {
            $query['inc_reg'] = 1;
        }
        $data       = null;
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren];

        try {
            $incidents = $this->sendRequest('get_list_v2.php', $query, __FUNCTION__, $siren);

            if (null !== $incidents = preg_replace('/(<\?xml .*\?>).*/', '', $incidents)) {
                try {
                    $data = $this->serializer->deserialize('<incidentList>' . $incidents . '</incidentList>', IncidentList::class, 'xml');
                } catch (\Exception $exception) {
                    $this->logger->error('Could not deserialize response: ' . $exception->getMessage() . ' siren: ' . $siren, $logContext);
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error('Call to get_list_v2 using params: ' . json_encode($query) . ' Error message: ' . $exception->getMessage(), $logContext);
        }

        return $data;
    }

    /**
     * @param $uri
     * @param array $query
     * @param string $method
     * @param string $siren
     * @return null|string
     */
    private function sendRequest($uri, array $query, $method, $siren)
    {
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren];
        $content    = null;
        try {
            $response = $this->client->get(
                $uri,
                [
                    'query'    => $query,
                    'on_stats' => $this->callHistoryManager->addResourceCallHistoryLog('codinf', $method, 'GET', $siren)
                ]
            );
            $content  = $response->getBody()->getContents();

            if (200 === $response->getStatusCode()) {
                $alertType = 'up';
                $this->logger->info('Call to ' . $uri . ' using params: ' . json_encode($query) . '. Response: ' . $content, $logContext);
            } else {
                $alertType = 'down';
                $this->logger->error('Call to ' . $uri . ' using params: ' . json_encode($query) . '. Response: ' . $content, $logContext);
            }
        } catch (\Exception $exception) {
            $alertType = 'down';
            $this->logger->error('Call to ' . $uri . 'using params: ' . json_encode($query) . '. Error message: ' . $exception->getMessage(), $logContext);
        }

        if ($this->monitoring) {
            $this->callHistoryManager->sendMonitoringAlert('Codinf', 'Codinf status', $alertType);
        }

        return $content;
    }
}
