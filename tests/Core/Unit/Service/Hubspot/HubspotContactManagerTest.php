<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Service\Hubspot;

use KLS\Core\Entity\Company;
use KLS\Core\Entity\HubspotContact;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Team;
use KLS\Core\Entity\User;
use KLS\Core\Entity\UserStatus;
use KLS\Core\Repository\HubspotContactRepository;
use KLS\Core\Repository\TemporaryTokenRepository;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Repository\UserSuccessfulLoginRepository;
use KLS\Core\Service\Hubspot\Client\HubspotClient;
use KLS\Core\Service\Hubspot\HubspotContactManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @coversDefaultClass \KLS\Core\Service\Hubspot\HubspotContactManager
 *
 * @internal
 */
class HubspotContactManagerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|HubspotClient */
    private $hubspotClient;

    /** @var ObjectProphecy|UserRepository */
    private $userRepository;

    /** @var ObjectProphecy|LoggerInterface */
    private $logger;

    /** @var ObjectProphecy|HubspotContactRepository */
    private $hubspotContactRepository;

    /** @var ObjectProphecy|TemporaryTokenRepository */
    private $temporaryTokenRepository;

    /** @var ObjectProphecy|UserSuccessfulLoginRepository */
    private $userSuccessfulLoginRepository;

    protected function setUp(): void
    {
        $this->hubspotClient                 = $this->prophesize(HubspotClient::class);
        $this->userRepository                = $this->prophesize(UserRepository::class);
        $this->logger                        = $this->prophesize(LoggerInterface::class);
        $this->hubspotContactRepository      = $this->prophesize(HubspotContactRepository::class);
        $this->temporaryTokenRepository      = $this->prophesize(TemporaryTokenRepository::class);
        $this->userSuccessfulLoginRepository = $this->prophesize(UserSuccessfulLoginRepository::class);
    }

    protected function tearDown(): void
    {
        $this->hubspotClient                 = null;
        $this->userRepository                = null;
        $this->logger                        = null;
        $this->hubspotContactRepository      = null;
        $this->temporaryTokenRepository      = null;
        $this->userSuccessfulLoginRepository = null;
    }

    /**
     * @covers ::getDailyApiUsage
     */
    public function testGetDailyApiUsageWithOkReturn(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $this->hubspotClient->getDailyUsageApi()->shouldBeCalledOnce()->willReturn($response->reveal());

        $response->getStatusCode()->shouldBeCalledOnce()->willReturn(Response::HTTP_OK);

        $content = [
            0 => [
                'name'         => 'api-calls-daily',
                'usageLimit'   => 250000,
                'currentUsage' => 19,
                'collectedAt'  => 1627403395098,
                'fetchStatus'  => 'SUCCESS',
                'resetsAt'     => 1627495200000,
            ],
        ];
        $response->getContent()->shouldBeCalledOnce()->willReturn(\json_encode($content));

        $result = $this->createTestObject()->getDailyApiUsage();

        static::assertArrayHasKey(0, $result);
        static::assertArrayHasKey('currentUsage', $result[0]);
    }

    /**
     * @covers ::getDailyApiUsage
     */
    public function testGetDailyApiUsageWithNotOkReturn(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $this->hubspotClient->getDailyUsageApi()->shouldBeCalledOnce()->willReturn($response->reveal());

        $response->getStatusCode()->shouldBeCalledOnce()->willReturn(Response::HTTP_BAD_REQUEST);

        $response->getContent()->shouldNotBeCalled();

        $result = $this->createTestObject()->getDailyApiUsage();

        static::assertEmpty($result);
    }

    /**
     * @covers ::importContacts
     */
    public function testImportContactsWithNoContactFound(): void
    {
        $response = $this->prophesize(ResponseInterface::class);

        $this->hubspotClient->fetchAllContacts(0)->shouldBeCalledOnce()->willReturn($response->reveal());
        $response->getStatusCode()->willReturn(Response::HTTP_OK);
        $response->getContent()->willReturn(\json_encode($this->getContact()));

        $this->hubspotContactRepository->findOneBy(['contactId' => 18051])->shouldBeCalled()->willReturn(null);

        $this->userRepository->findOneBy(['email' => 'test@test.fr'])->shouldBeCalledOnce()->willReturn(null);

        $this->hubspotContactRepository->persist(Argument::any())->shouldNotBeCalled();
        $this->hubspotContactRepository->flush()->shouldBeCalledOnce();

        $result = $this->createTestObject()->importContacts(0);
        static::assertArrayHasKey('lastContactId', $result);
        static::assertArrayHasKey('contactAddedNb', $result);
        static::assertSame(0, $result['lastContactId']);
        static::assertSame(0, $result['contactAddedNb']);
    }

    /**
     * @covers ::importContacts
     */
    public function testImportContactsWithAddContact(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $user     = new User('test@test.fr');

        $this->hubspotClient->fetchAllContacts(0)->shouldBeCalledOnce()->willReturn($response->reveal());
        $response->getStatusCode()->willReturn(Response::HTTP_OK);
        $response->getContent()->willReturn(\json_encode($this->getContact()));

        $this->hubspotContactRepository->findOneBy(['contactId' => 18051])->shouldBeCalled()->willReturn(null);

        $this->userRepository->findOneBy(['email' => 'test@test.fr'])->shouldBeCalledOnce()->willReturn($user);

        $this->hubspotContactRepository->persist(Argument::type(HubspotContact::class))->shouldBeCalledOnce();
        $this->hubspotContactRepository->flush()->shouldBeCalledOnce();

        $result = $this->createTestObject()->importContacts(0);
        static::assertArrayHasKey('lastContactId', $result);
        static::assertArrayHasKey('contactAddedNb', $result);
        static::assertSame(0, $result['lastContactId']);
        static::assertSame(1, $result['contactAddedNb']);
    }

    /**
     * @covers ::importContacts
     */
    public function testImportContactWithNoResult(): void
    {
        $response = $this->prophesize(ResponseInterface::class);

        $this->hubspotClient->fetchAllContacts(0)->shouldBeCalledOnce()->willReturn($response->reveal());
        $response->getStatusCode()->willReturn(Response::HTTP_OK);
        $response->getContent()->willReturn(\json_encode([]));

        $this->hubspotContactRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->userRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        $this->hubspotContactRepository->persist(Argument::any())->shouldNotBeCalled();
        $this->hubspotContactRepository->flush()->shouldNotBeCalled();

        $result = $this->createTestObject()->importContacts(0);

        static::assertArrayHasKey('lastContactId', $result);
        static::assertArrayHasKey('contactAddedNb', $result);
    }

    /**
     * @covers ::exportUsers
     */
    public function testExportUsersWithNoUsersToCreateAndUpdate(): void
    {
        $this->userRepository->findHubspotUsersToCreate(2)->shouldBeCalledOnce()->willReturn(null);
        $this->userRepository->findHubspotUsersToUpdate(2)->shouldBeCalledOnce()->willReturn(null);
        $this->temporaryTokenRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->userSuccessfulLoginRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->hubspotClient->updateContact(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->hubspotContactRepository->persist(Argument::any())->shouldNotBeCalled();
        $this->hubspotClient->postNewContact(Argument::any())->shouldNotBeCalled();
        $this->hubspotContactRepository->flush()->shouldBeCalled();

        $result = $this->createTestObject()->exportUsers(2);

        static::assertArrayHasKey('usersUpdated', $result);
        static::assertArrayHasKey('usersCreated', $result);
    }

    /**
     * @covers ::exportUsers
     */
    public function testExportUsersWithUsersToCreate(): void
    {
        $arrUsers = [
            0 => $this->createUser(),
        ];

        $this->userRepository->findHubspotUsersToUpdate(1)->shouldBeCalledOnce()->willReturn(null);
        $this->userRepository->findHubspotUsersToCreate(1)->shouldBeCalled()->willReturn($arrUsers);

        $response = $this->prophesize(ResponseInterface::class);

        $this->hubspotClient->updateContact(Argument::any(), Argument::any())->shouldNotBeCalled();

        $response->getStatusCode()->shouldBeCalledOnce()->willReturn(Response::HTTP_CREATED);
        $this->hubspotClient->postNewContact(Argument::any())->shouldBeCalledOnce()->willReturn($response->reveal());
        $response->getContent()->shouldBeCalled()->willReturn(\json_encode(
            [
                'id'         => 1234,
                'archived'   => false,
                'properties' => [
                    'property_number' => '17',
                ],
                'updatedAt' => '2019-12-07T16:50:06.678Z',
            ],
            JSON_THROW_ON_ERROR
        ));

        $this->hubspotContactRepository->persist(Argument::type(HubspotContact::class))->shouldBeCalledOnce();
        $this->hubspotContactRepository->flush()->shouldBeCalledTimes(2);

        $result = $this->createTestObject()->exportUsers(1);

        static::assertArrayHasKey('usersUpdated', $result);
        static::assertArrayHasKey('usersCreated', $result);
    }

    /**
     * @covers ::exportUsers
     */
    public function testExportUsersWithUsersToUpdate(): void
    {
        $arrUsers = [
            0 => $this->createUser(),
        ];

        $this->userRepository->findHubspotUsersToCreate(1)->shouldBeCalled()->willReturn(null);
        $this->userRepository->findHubspotUsersToUpdate(1)->shouldBeCalledOnce()->willReturn($arrUsers);

        $response = $this->prophesize(ResponseInterface::class);

        $hubspotContact = new HubspotContact($arrUsers[0], 10);

        $this->hubspotContactRepository->findOneBy(['user' => $arrUsers[0]])->shouldBeCalled()->willReturn($hubspotContact);

        $response->getStatusCode()->shouldBeCalled()->willReturn(Response::HTTP_OK);

        $this->hubspotClient->updateContact($hubspotContact->getContactId(), $this->getFormatData())->shouldBeCalledOnce()->willReturn($response->reveal());

        $this->hubspotContactRepository->flush()->shouldBeCalledTimes(2);

        $result = $this->createTestObject()->exportUsers(1);

        static::assertArrayHasKey('usersUpdated', $result);
        static::assertArrayHasKey('usersCreated', $result);
    }

    public function getContact(): array
    {
        return [
            'results' => [
                0 => [
                    'id'         => '18051',
                    'properties' => [
                        'createdate' => '2021-07-27T09:08:59.969Z',
                        'email'      => 'test@test.fr',
                        'firstname'  => 'Test',
                        'lastname'   => 'test',
                    ],
                    'createdAt' => '2021-07-27T09:08:59.969Z',
                    'updatedAt' => '2021-07-29T10:27:26.305Z',
                    'archived'  => false,
                ],
            ],
        ];
    }

    private function createUser(): User
    {
        $user = new User('user_1@test.com');
        $user->setFirstName('Alain');
        $user->setLastName('Antoinette');
        $user->setJobFunction('job');
        $user->setPhone('+33600000000');
        $user->setCurrentStatus(new UserStatus($user, 20));

        $user->setCurrentStaff($this->getStaff());

        return $user;
    }

    private function getCompany(): Company
    {
        return new Company('displayName', 'CompanyName', 'siren');
    }

    private function getStaff(): Staff
    {
        $staff = new Staff(new User('test@mail.fr'), Team::createRootTeam($this->getCompany()));
        $staff->setAgencyProjectCreationPermission(true);
        $staff->setArrangementProjectCreationPermission(true);

        return $staff;
    }

    private function createTestObject(): HubspotContactManager
    {
        return new HubspotContactManager(
            $this->hubspotClient->reveal(),
            $this->userRepository->reveal(),
            $this->logger->reveal(),
            $this->hubspotContactRepository->reveal(),
            $this->temporaryTokenRepository->reveal(),
            $this->userSuccessfulLoginRepository->reveal()
        );
    }

    private function getFormatData(): array
    {
        $user = $this->createUser();

        return [
            'properties' => [
                'firstname'                      => $user->getFirstName(),
                'lastname'                       => $user->getLastName(),
                'email'                          => $user->getEmail(),
                'jobtitle'                       => $user->getJobFunction(),
                'phone'                          => $user->getPhone(),
                'kls_user_status'                => 'created',
                'kls_last_login'                 => null,
                'kls_init_token_expiry'          => null,
                'kls_user_staff'                 => null,
                'kls_user_manager'               => null,
                'kls_user_admin'                 => null,
                'kls_staff_arrangement_creation' => null,
                'kls_staff_agency_creation'      => null,
            ],
        ];
    }
}
