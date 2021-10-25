<?php

declare(strict_types=1);

namespace KLS\Core\Service\Hubspot;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JsonException;
use KLS\Core\Entity\HubspotContact;
use KLS\Core\Entity\User;
use KLS\Core\Entity\UserStatus;
use KLS\Core\Repository\HubspotContactRepository;
use KLS\Core\Repository\TemporaryTokenRepository;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Repository\UserSuccessfulLoginRepository;
use KLS\Core\Service\Hubspot\Client\HubspotClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HubspotContactManager
{
    private HubspotClient $hubspotClient;
    private UserRepository $userRepository;
    private LoggerInterface $logger;
    private HubspotContactRepository $hubspotContactRepository;
    private TemporaryTokenRepository $temporaryTokenRepository;
    private UserSuccessfulLoginRepository $userSuccessfulLoginRepository;

    public function __construct(
        HubspotClient $hubspotClient,
        UserRepository $userRepository,
        LoggerInterface $logger,
        HubspotContactRepository $hubspotContactRepository,
        TemporaryTokenRepository $temporaryTokenRepository,
        UserSuccessfulLoginRepository $userSuccessfulLoginRepository
    ) {
        $this->hubspotClient                 = $hubspotClient;
        $this->userRepository                = $userRepository;
        $this->logger                        = $logger;
        $this->hubspotContactRepository      = $hubspotContactRepository;
        $this->temporaryTokenRepository      = $temporaryTokenRepository;
        $this->userSuccessfulLoginRepository = $userSuccessfulLoginRepository;
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
     * Hubspot contact (corresponding to our users) to our database.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws JsonException
     */
    public function importContacts(int $lastContactId = 0): array
    {
        $content = $this->fetchContacts($lastContactId);

        if (false === \array_key_exists('results', $content)) {
            $this->logger->info('No contacts found, try to add users from our database');

            return [
                'contactAddedNb' => 0,
                'lastContactId'  => 0,
            ];
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

        $lastContactId = 0;
        if (\array_key_exists('paging', $content)) {
            $lastContactId = (int) $content['paging']['next']['after'];
        }

        return [
            'lastContactId'  => $lastContactId,
            'contactAddedNb' => $contactAddedNb,
        ];
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function exportUsers(int $limit): array
    {
        $usersCreated = 0;
        $usersUpdated = 0;

        // Get all users when a corresponding hubspot id does not exist
        $users = $this->userRepository->findHubspotUsersToCreate($limit);

        if ($users) {
            foreach ($users as $user) {
                if (false === $this->createContactOnHubspot($user)) {
                    continue;
                }
                ++$usersCreated;
            }
        }

        //In case of we got an error on the part below, our synchronized users won't be flush on our database
        $this->hubspotContactRepository->flush();

        // Get all users when a corresponding hubspot id exist
        $users = $this->userRepository->findHubspotUsersToUpdate($limit);

        if ($users) {
            foreach ($users as $user) {
                $hubspotContact = $this->hubspotContactRepository->findOneBy(['user' => $user]);

                if (false === $hubspotContact instanceof HubspotContact) {
                    continue;
                }

                if ($this->updateContactOnHubspot($hubspotContact, $this->formatData($user))) {
                    ++$usersUpdated;
                }
            }
        }

        $this->hubspotContactRepository->flush();

        return [
            'usersCreated' => $usersCreated,
            'usersUpdated' => $usersUpdated,
        ];
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws JsonException
     */
    private function fetchContacts(int $lastContactId = 0): array
    {
        $response = $this->hubspotClient->fetchAllContacts($lastContactId);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $this->logger->error(\sprintf(
                'There is an error while fetching %s : Error is %s',
                $response->getInfo()['url'],
                $response->getContent(false)
            ));

            return [];
        }

        return \json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function formatData(User $user): array
    {
        $temporaryToken = $this->temporaryTokenRepository->findOneBy(['user' => $user], ['id' => 'DESC']);
        $lastLogin      = $this->userSuccessfulLoginRepository->findOneBy([
            'user' => $user,
        ], ['id' => 'DESC']); // get by user order by date desc, prendre le premier
        $temporaryTokenExpires = $temporaryToken ? $temporaryToken->getExpires() : null;
        $lastLoginDate         = $lastLogin ? $lastLogin->getAdded() : null;

        $data = [
            'staffArrangementCreation' => [],
            'staffAgencyCreation'      => [],
            'userManager'              => [],
            'userAdmin'                => [],
            'userStaff'                => [],
        ];

        foreach ($user->getStaff() as $staff) {
            if ($staff->isActive()) {
                if ($staff->hasArrangementProjectCreationPermission()) {
                    $data['staffArrangementCreation'][] = $staff->getCompany()->getDisplayName();
                }
                if ($staff->hasAgencyProjectCreationPermission()) {
                    $data['staffAgencyCreation'][] = $staff->getCompany()->getDisplayName();
                }
                if ($staff->isManager()) {
                    $data['userManager'][] = $staff->getCompany()->getDisplayName();
                }
                if ($staff->isAdmin()) {
                    $data['userAdmin'][] = $staff->getCompany()->getDisplayName();
                }
                $data['userStaff'][] = $staff->getCompany()->getDisplayName();
            }
        }

        return [
            'properties' => [
                'firstname'       => $user->getFirstName(),
                'lastname'        => $user->getLastName(),
                'email'           => $user->getEmail(),
                'jobtitle'        => $user->getJobFunction(),
                'phone'           => $user->getPhone(),
                'kls_user_status' => UserStatus::STATUS_INVITED
                                     === $user->getCurrentStatus()->getStatus() ? 'invited' : 'created',
                'kls_last_login'        => $lastLoginDate ? $lastLoginDate->format('Y-m-d') : null,
                'kls_init_token_expiry' => $temporaryTokenExpires ?
                                           $temporaryTokenExpires->format('Y-m-d') : null,
                'kls_user_staff'                 => \implode("\n", $data['userStaff']),
                'kls_user_manager'               => \implode("\n", $data['userManager']),
                'kls_user_admin'                 => \implode("\n", $data['userAdmin']),
                'kls_staff_arrangement_creation' => \implode("\n", $data['staffArrangementCreation']),
                'kls_staff_agency_creation'      => \implode("\n", $data['staffAgencyCreation']),
            ],
        ];
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function updateContactOnHubspot(HubspotContact $hubspotContact, array $data): bool
    {
        $response = $this->hubspotClient->updateContact($hubspotContact->getContactId(), $data);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $this->logger->info($response->getContent(false));

            return false;
        }

        $hubspotContact->synchronize();

        return true;
    }

    /**
     * Flow: Post new contact on hubspot. If 201, we create the relation between our user and the contact Id that
     * Hubspot just sent us back.
     *
     * @throws ORMException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws JsonException
     */
    private function createContactOnHubspot(User $user): bool
    {
        $data = $this->formatData($user);

        $response = $this->hubspotClient->postNewContact($data);

        if (Response::HTTP_CREATED !== $response->getStatusCode()) {
            $this->logger->info($response->getContent(false));

            return false;
        }

        $content = \json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $hubspotContact = new HubspotContact($user, (int) $content['id']);
        $hubspotContact->synchronize();
        $this->hubspotContactRepository->persist($hubspotContact);

        return true;
    }
}
