<?php

declare(strict_types=1);

namespace Unilend\Service;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Unilend\Entity\Clients;
use Unilend\Entity\ClientsStatus;
use Unilend\Entity\ClientsStatusHistory;
use Unilend\Repository\ClientStatusRepository;

/**
 * Class ClientManager.
 */
class ClientManager
{
    /**
     * @var ClientStatusRepository
     */
    private $clientStatusRepository;

    /**
     * ClientManager constructor.
     *
     * @param ClientStatusRepository $clientStatusRepository
     */
    public function __construct(
        ClientStatusRepository $clientStatusRepository
    ) {
        $this->clientStatusRepository = $clientStatusRepository;
    }

    /**
     * @param Clients              $clients
     * @param string|ClientsStatus $status
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function setStatus(Clients $clients, $status)
    {
        if (!$status instanceof ClientsStatus) {
            $status = $this->clientStatusRepository->findOneByCode($status);
        }

        $statusHistoryEntry = new ClientsStatusHistory();
        $statusHistoryEntry->setIdClient($clients);
        $statusHistoryEntry->setIdStatus($status);

        $clients->setIdClientStatusHistory($statusHistoryEntry);
    }
}
