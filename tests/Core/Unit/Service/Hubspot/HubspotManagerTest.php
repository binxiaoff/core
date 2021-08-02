<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Unit\Service\Hubspot;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Unilend\Core\Entity\HubspotContact;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\HubspotContactRepository;
use Unilend\Core\Repository\UserRepository;
use Unilend\Core\Service\Hubspot\Client\HubspotClient;
use Unilend\Core\Service\Hubspot\HubspotManager;

/**
 * @coversDefaultClass \Unilend\Core\Service\Hubspot\HubspotManager
 *
 * @internal
 */
class HubspotManagerTest extends TestCase
{
    /** @var ObjectProphecy|HubspotClient */
    private $hubspotClient;

    /** @var ObjectProphecy|UserRepository */
    private $userRepository;

    /** @var ObjectProphecy|LoggerInterface */
    private $logger;

    /** @var ObjectProphecy|HubspotContactRepository */
    private $hubspotContactRepository;

    protected function setUp(): void
    {
        $this->hubspotClient            = $this->prophesize(HubspotClient::class);
        $this->userRepository           = $this->prophesize(UserRepository::class);
        $this->logger                   = $this->prophesize(LoggerInterface::class);
        $this->hubspotContactRepository = $this->prophesize(HubspotContactRepository::class);
    }

    protected function tearDown(): void
    {
        $this->hubspotClient            = null;
        $this->userRepository           = null;
        $this->logger                   = null;
        $this->hubspotContactRepository = null;
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
     * @covers ::synchronizeContact
     */
    public function testSynchronizeContactsWithNoContactFound(): void
    {
        $response = $this->prophesize(ResponseInterface::class);

        $this->hubspotClient->fetchAllContacts(null)->shouldBeCalledOnce()->willReturn($response->reveal());
        $response->getStatusCode()->willReturn(Response::HTTP_OK);
        $response->getContent()->willReturn(\json_encode($this->getContact()));

        $this->hubspotContactRepository->findOneBy(['contactId' => 18051])->shouldBeCalled()->willReturn(null);

        $this->userRepository->findOneBy(['email' => 'test@test.fr'])->shouldBeCalledOnce()->willReturn(null);

        $this->hubspotContactRepository->persist(Argument::any())->shouldNotBeCalled();
        $this->hubspotContactRepository->flush()->shouldBeCalledOnce();

        $result = $this->createTestObject()->synchronizeContacts(null);
        static::assertArrayHasKey('lastContactId', $result);
        static::assertArrayHasKey('contactAddedNb', $result);
        static::assertNull($result['lastContactId']);
        static::assertSame(0, $result['contactAddedNb']);
    }

    /**
     * @covers ::synchronizeContact
     */
    public function testSynchronizeContactsWithAddContact(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $user     = new User('test@test.fr');

        $this->hubspotClient->fetchAllContacts(null)->shouldBeCalledOnce()->willReturn($response->reveal());
        $response->getStatusCode()->willReturn(Response::HTTP_OK);
        $response->getContent()->willReturn(\json_encode($this->getContact()));

        $this->hubspotContactRepository->findOneBy(['contactId' => 18051])->shouldBeCalled()->willReturn(null);

        $this->userRepository->findOneBy(['email' => 'test@test.fr'])->shouldBeCalledOnce()->willReturn($user);

        $this->hubspotContactRepository->persist(Argument::type(HubspotContact::class))->shouldBeCalledOnce();
        $this->hubspotContactRepository->flush()->shouldBeCalledOnce();

        $result = $this->createTestObject()->synchronizeContacts(null);
        static::assertArrayHasKey('lastContactId', $result);
        static::assertArrayHasKey('contactAddedNb', $result);
        static::assertNull($result['lastContactId']);
        static::assertSame(1, $result['contactAddedNb']);
    }

    public function testSynchronizeContactWithNoResult(): void
    {
        $response = $this->prophesize(ResponseInterface::class);

        $this->hubspotClient->fetchAllContacts(null)->shouldBeCalledOnce()->willReturn($response->reveal());
        $response->getStatusCode()->willReturn(Response::HTTP_OK);
        $response->getContent()->willReturn(\json_encode([]));

        $this->hubspotContactRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->userRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        $this->hubspotContactRepository->persist(Argument::any())->shouldNotBeCalled();
        $this->hubspotContactRepository->flush()->shouldNotBeCalled();

        $result = $this->createTestObject()->synchronizeContacts(null);
        static::assertEmpty($result);
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

    public function getFakeContentReturn(): array
    {
        return [
            'blablabla' => [],
        ];
    }

    private function createTestObject(): HubspotManager
    {
        return new HubspotManager(
            $this->hubspotClient->reveal(),
            $this->userRepository->reveal(),
            $this->logger->reveal(),
            $this->hubspotContactRepository->reveal()
        );
    }
}
