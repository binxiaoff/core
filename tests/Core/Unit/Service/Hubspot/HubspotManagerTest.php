<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Unit\Service\Hubspot\Client;

use _HumbugBox373c0874430e\Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Unilend\Core\Repository\UserRepository;
use Unilend\Core\Service\Hubspot\Client\HubspotClient;
use Unilend\Core\Service\Hubspot\HubspotManager;

/**
 * @internal
 *
 * @coversDefaultClass /Unilend/Core/Service/Hubspot/HubspotManager
 */
class HubspotManagerTest extends TestCase
{
    /** @var ObjectProphecy|HubspotClient */
    private $hubspotClient;

    /** @var ObjectProphecy|\Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var ObjectProphecy|UserRepository */
    private $userRepository;

    /** @var ObjectProphecy|LoggerInterface */
    private $logger;

    protected function setUp(): void
    {
        $this->hubspotClient  = $this->prophesize(HubspotClient::class);
        $this->userRepository = $this->prophesize(UserRepository::class);
        $this->entityManager  = $this->prophesize(EntityManagerInterface::class);
        $this->logger         = $this->prophesize(LoggerInterface::class);
    }

    protected function tearDown(): void
    {
        $this->hubspotClient = null;
    }

    public function testGetDailyApiUsage(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $this->hubspotClient->getDailyUsageApi()->shouldBeCalledOnce()->willReturn($response);

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

        $this->createTestObject()->getDailyApiUsage();
        static::assertArrayHasKey(0, $content);
    }

    public function testFetchContact(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $this->hubspotClient->fetchAllContacts()->shouldBeCalledOnce()->willReturn($response);
        $response->getStatusCode()->shouldBeCalledOnce()->willReturn(Response::HTTP_OK);

        $content = [
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

        $response->getContent()->shouldBeCalledOnce()->willReturn(\json_encode($content));
        $this->createTestObject()->fetchContacts();
        static::assertArrayHasKey('results', $content);
    }

    private function createTestObject(): HubspotManager
    {
        return new HubspotManager(
            $this->hubspotClient->reveal(),
            $this->userRepository->reveal(),
            $this->entityManager->reveal(),
            $this->logger->reveal()
        );
    }
}
