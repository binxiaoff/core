<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Unilend\Entity\{Clients, TemporaryToken};
use Unilend\Repository\ClientsRepository;

class UpdateClientViaTemporaryToken extends AbstractController
{
    /** @var ClientsRepository */
    private $clientRepository;

    /**
     * @param ClientsRepository $clientRepository
     */
    public function __construct(ClientsRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * @param TemporaryToken $data
     *
     * @throws Exception
     *
     * @return Clients
     */
    public function __invoke(TemporaryToken $data): Clients
    {
        $client = $data->getClient();
        $this->clientRepository->save($client);

        return $client;
    }
}
