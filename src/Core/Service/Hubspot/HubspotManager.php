<?php

declare(strict_types=1);

namespace Unilend\Core\Service\Hubspot;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Unilend\Core\Entity\HubspotContact;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\UserRepository;
use Unilend\Core\Service\Hubspot\Client\HubspotClient;

class HubspotManager
{
    private HubspotClient $hubspotIntegrationClient;
    private HubspotClient $hubspotClient;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        HubspotClient $hubspotIntegrationClient
        HubspotClient $hubspotClient,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->hubspotIntegrationClient = $hubspotIntegrationClient;
        $this->hubspotClient  = $hubspotClient;
        $this->userRepository = $userRepository;
        $this->entityManager  = $entityManager;
        $this->logger         = $logger;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getDailyApiUsage(): array
    {
        $response = $this->hubspotIntegrationClient->getDailyUsageApi();

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return [];
        }

        return \json_decode($response->getContent(), true);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function fetchContacts(array $params = [])
    {
        $response = $this->hubspotClient->fetchAllContacts($params);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $this->logger->error(\sprintf('There is an error while fetching %s', $response->getInfo()['url']));

            return [];
        }

        return \json_decode($response->getContent(), true);
    }

    /**
     * Array of Hubspot contacts given. Check if the hubspot contact exist, if not we create it.
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function handleContacts(array $response, ?int $limit = -1): void
    {
        $contactAdded = $limit > 0 ? $limit : 0;

        foreach ($response['results'] as $contact) {
            $user = $this->userRepository->findOneBy(['email' => $contact['properties']['email']]);

            if (false === $user instanceof User) { // If we don't find any user with the email given
                continue;
            }

            if ($user->getHubspotContact()) { // if the user found has already a contact Id
                continue;
            }

            if ($contactAdded >= 2) { // If the limit of the number of user we want to insert is reached
                continue;
            }

            $this->addHubspotContact($user, (int) $contact['id']);
            ++$contactAdded;
        }

        if ($contactAdded >= 2) {
            $this->entityManager->flush();

            return;
        }

        if (\array_key_exists('paging', $response)) {
            $responseNext = $this->fetchContacts(['after' => $response['paging']['next']['after']]);

            if (\array_key_exists('results', $responseNext)) {
                $this->handleContacts($responseNext, $contactAdded);
            }
        }

        $this->entityManager->flush();
    }

    public function addHubspotContact(User $user, int $contactId): void
    {
        $hubspotContact = new HubspotContact($user, $contactId);
        $this->entityManager->persist($hubspotContact);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function handlePagination(array $paging): void
    {
        $response = $this->fetchContacts(['after' => $paging['next']['after']]);

        if ($response['results']) {
            $this->handleContacts($response);
        }

        if (isset($response['paging'])) {
            $this->handlePagination($response['paging']);
        }
    }
}
