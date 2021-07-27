<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Unit\Service\Hubspot\Client;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Unilend\Core\Service\Hubspot\Client\HubspotClient;
use Unilend\Core\Service\Hubspot\HubspotManager;

/**
 * @internal
 *
 * @coversNothing
 */
class HubspotManagerTest extends TestCase
{
    /** @var ObjectProphecy|HubspotClient */
    private $hubspotClient;

    protected function setUp(): void
    {
        $this->hubspotClient = $this->prophesize(HubspotClient::class);
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

    private function createTestObject(): HubspotManager
    {
        return new HubspotManager(
            $this->hubspotClient->reveal()
        );
    }
}
