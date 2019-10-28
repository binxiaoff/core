<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Unilend\Entity\{Clients, TemporaryToken};
use Unilend\Repository\ClientsRepository;

class UpdateClientViaTemporaryToken extends AbstractController
{
    /** @var SerializerInterface */
    private $serializer;
    /** @var ClientsRepository */
    private $clientRepository;

    /**
     * @param SerializerInterface $serializer
     * @param ClientsRepository   $clientRepository
     */
    public function __construct(SerializerInterface $serializer, ClientsRepository $clientRepository)
    {
        $this->serializer       = $serializer;
        $this->clientRepository = $clientRepository;
    }

    /**
     * @param TemporaryToken $data
     * @param Request        $request
     *
     * @throws Exception
     *
     * @return Clients
     */
    public function __invoke(TemporaryToken $data, Request $request): Clients
    {
        if (false === $data->isValid()) {
            throw new AccessDeniedHttpException('Temporary token expired');
        }
        $client = $data->getClient();
        $this->serializer->deserialize($request->getContent(), Clients::class, 'json', ['object_to_populate' => $client]);
        $this->clientRepository->save($client);

        return $client;
    }
}
