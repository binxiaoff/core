<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

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

    /**
     * CodinfManager constructor.
     *
     * @param ClientInterface $client
     * @param $user
     * @param $password
     * @param $baseUrl
     * @param LoggerInterface $logger
     * @param CallHistoryManager $callHistoryManager
     */
    public function __construct(ClientInterface $client, $user, $password, $baseUrl, LoggerInterface $logger, CallHistoryManager $callHistoryManager)
    {
        $this->client             = $client;
        $this->user               = $user;
        $this->password           = $password;
        $this->logger             = $logger;
        $this->callHistoryManager = $callHistoryManager;
    }

    /**
     * @param $siren
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param bool $includeRegularized
     * @return ResponseInterface|\SimpleXMLElement
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

        return $this->client->get(
            'get_list_v2.php',
            [
                'query'    => $query,
                'on_stats' => $this->callHistoryManager->addResourceCallHistoryLog('codinf', __FUNCTION__, 'GET', $siren)
            ]
        );
    }
}
