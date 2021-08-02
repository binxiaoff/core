<?php

declare(strict_types=1);

namespace KLS\Core\Service\Hubspot;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JsonException;
use KLS\Core\Entity\HubspotContact;
use KLS\Core\Entity\User;
use KLS\Core\Repository\HubspotContactRepository;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Service\Hubspot\Client\HubspotClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HubspotManager
{
    private HubspotClient $hubspotCrmClient;
    private UserRepository $userRepository;
    private LoggerInterface $logger;
    private HubspotContactRepository $hubspotContactRepository;

    public function __construct(
        HubspotClient $hubspotClient,
        UserRepository $userRepository,
        LoggerInterface $logger,
        HubspotContactRepository $hubspotContactRepository
    ) {
        $this->hubspotClient            = $hubspotClient;
        $this->userRepository           = $userRepository;
        $this->logger                   = $logger;
        $this->hubspotContactRepository = $hubspotContactRepository;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws JsonException
     */
    public function getDailyApiUsage(): array
    {
        $response = $this->hubspotClient->getDailyUsageApi();

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return [];
        }

        return \json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws JsonException
     */
    public function synchronizeContacts(?int $lastContactId = null): array
    {
        $content = $this->fetchContacts($lastContactId);

        if (!\array_key_exists('results', $content)) {
            $this->logger->info('No contacts found, try to add users from our database');

            return [];
        }

        $contactAddedNb = 0;

        foreach ($content['results'] as $contact) {
            $userHubspotContact = $this->hubspotContactRepository->findOneBy(['contactId' => (int) $contact['id']]);

            if (true === $userHubspotContact instanceof HubspotContact) { // if the user found has already a contact Id
                continue;
            }

            $user = $this->userRepository->findOneBy(['email' => $contact['properties']['email']]);

            if (false === $user instanceof User) { //If the user does not exist in our database
                continue;
            }

            $hubspotContact = new HubspotContact($user, (int) $contact['id']);
            $this->hubspotContactRepository->persist($hubspotContact);

            ++$contactAddedNb;
        }

        $this->hubspotContactRepository->flush();

        $lastContactId = null;
        if (\array_key_exists('paging', $content)) {
            $lastContactId = $content['paging']['next']['after'];
        }

        return [
            'lastContactId'  => $lastContactId,
            'contactAddedNb' => $contactAddedNb,
        ];
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws JsonException
     */
    private function fetchContacts(?int $lastContactId = null): array
    {
        $response = $this->hubspotClient->fetchAllContacts($lastContactId);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $this->logger->error(\sprintf('There is an error while fetching %s', $response->getInfo()['url']));

            return [];
        }

        return \json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
