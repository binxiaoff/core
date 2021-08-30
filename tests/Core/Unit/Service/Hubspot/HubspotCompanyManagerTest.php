<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Service\Hubspot;

use KLS\Core\Entity\Company;
use KLS\Core\Entity\HubspotCompany;
use KLS\Core\Repository\CompanyRepository;
use KLS\Core\Repository\HubspotCompanyRepository;
use KLS\Core\Service\Hubspot\Client\HubspotClient;
use KLS\Core\Service\Hubspot\HubspotCompanyManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @coversDefaultClass \KLS\Core\Service\Hubspot\HubspotCompanyManager
 *
 * @internal
 */
class HubspotCompanyManagerTest extends TestCase
{
    /** @var ObjectProphecy|LoggerInterface */
    private $logger;

    /** @var HubspotCompanyRepository|ObjectProphecy */
    private $hubspotCompanyRepository;

    /** @var CompanyRepository|ObjectProphecy */
    private $companyRepository;

    /** @var HubspotClient|ObjectProphecy */
    private $hubspotClient;

    protected function setUp(): void
    {
        $this->logger                   = $this->prophesize(LoggerInterface::class);
        $this->hubspotCompanyRepository = $this->prophesize(HubspotCompanyRepository::class);
        $this->companyRepository        = $this->prophesize(CompanyRepository::class);
        $this->hubspotClient            = $this->prophesize(HubspotClient::class);
    }

    protected function tearDown(): void
    {
        $this->logger                   = null;
        $this->hubspotCompanyRepository = null;
        $this->companyRepository        = null;
        $this->hubspotClient            = null;
    }

    /**
     * @covers ::synchronizeCompany
     */
    public function testSynchronizeCompaniesWithNoCompany(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $this->hubspotClient->fetchAllCompanies(0)->shouldBeCalledOnce()->willReturn($response->reveal());
        $response->getStatusCode()->willReturn(Response::HTTP_OK);
        $response->getContent()->willReturn(\json_encode([], JSON_THROW_ON_ERROR));

        $this->hubspotCompanyRepository->findOneBy(['hubspotCompanyId' => Argument::any()])->shouldNotBeCalled();
        $this->companyRepository->findOneBy(['shortCode' => Argument::any()])->shouldNotBeCalled();

        $this->hubspotCompanyRepository->flush()->shouldNotBeCalled();

        $result = $this->createTestObject()->synchronizeCompanies(0);

        static::assertEmpty($result);
    }

    /**
     * @covers ::synchronizeCompany
     */
    public function testSynchronizeWithHubspotCompaniesAlreadyExisted(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $this->hubspotClient->fetchAllCompanies(0)->shouldBeCalledOnce()->willReturn($response->reveal());
        $response->getStatusCode()->willReturn(Response::HTTP_OK);
        $response->getContent()->willReturn(\json_encode($this->getHubspotCompanyCreatedResponse(), JSON_THROW_ON_ERROR));

        $this->hubspotCompanyRepository->findOneBy(['hubspotCompanyId' => '6584949386'])->shouldBeCalled()
            ->willReturn(new HubspotCompany($this->getCompany(), '6584949386'))
        ;
        $this->companyRepository->findOneBy(['shortCode' => Argument::any()])->shouldNotBeCalled();

        $this->hubspotCompanyRepository->flush()->shouldBeCalled();

        $result = $this->createTestObject()->synchronizeCompanies(0);

        static::assertArrayHasKey('lastCompanyId', $result);
        static::assertArrayHasKey('companyAddedNb', $result);
    }

    /**
     * @covers ::synchronizeCompany
     */
    public function testSynchronizeWithCompanyNotFoundInDB(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $this->hubspotClient->fetchAllCompanies(0)->shouldBeCalledOnce()->willReturn($response->reveal());
        $response->getStatusCode()->willReturn(Response::HTTP_OK);
        $response->getContent()->willReturn(\json_encode($this->getHubspotCompanyCreatedResponse(), JSON_THROW_ON_ERROR));

        $this->hubspotCompanyRepository->findOneBy(['hubspotCompanyId' => '6584949386'])->shouldBeCalled()->willReturn(null);
        $this->companyRepository->findOneBy(['shortCode' => 'KLS'])->shouldBeCalledOnce()->willReturn(null);

        $this->hubspotCompanyRepository->persist(Argument::any())->shouldNotBeCalled();
        $this->hubspotCompanyRepository->flush()->shouldBeCalled();

        $result = $this->createTestObject()->synchronizeCompanies(0);

        static::assertArrayHasKey('lastCompanyId', $result);
        static::assertArrayHasKey('companyAddedNb', $result);
        static::assertSame(0, $result['lastCompanyId']);
        static::assertSame(0, $result['companyAddedNb']);
    }

    /**
     * @covers ::synchronizeCompany
     */
    public function testSynchronizeHubspotCompanies(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $this->hubspotClient->fetchAllCompanies(0)->shouldBeCalledOnce()->willReturn($response->reveal());
        $response->getStatusCode()->willReturn(Response::HTTP_OK);
        $response->getContent()->willReturn(\json_encode($this->getHubspotCompanyCreatedResponse(), JSON_THROW_ON_ERROR));

        $this->hubspotCompanyRepository->findOneBy(['hubspotCompanyId' => '6584949386'])->shouldBeCalled()->willReturn(null);
        $this->companyRepository->findOneBy(['shortCode' => 'KLS'])->shouldBeCalledOnce()->willReturn($this->getCompany());

        $this->hubspotCompanyRepository->persist(Argument::type(HubspotCompany::class));
        $this->hubspotCompanyRepository->flush()->shouldBeCalled();

        $result = $this->createTestObject()->synchronizeCompanies(0);

        static::assertArrayHasKey('lastCompanyId', $result);
        static::assertArrayHasKey('companyAddedNb', $result);
        static::assertSame(0, $result['lastCompanyId']);
        static::assertSame(1, $result['companyAddedNb']);
    }

    public function getHubspotCompanyCreatedResponse(): array
    {
        return [
            'results' => [
                0 => [
                    'id'         => '6584949386',
                    'properties' => [
                        'createdate'          => '2021-07-16T10:19:04.834Z',
                        'domain'              => 'biglytics.net',
                        'hs_lastmodifieddate' => '2021-08-06T10:04:48.250Z',
                        'hs_object_id'        => '6584949386',
                        'kls_short_code'      => 'KLS',
                    ],
                    'createdAt' => '2021-07-16T10:19:04.834Z',
                    'updatedAt' => '2021-08-06T10:04:48.250Z',
                    'archived'  => false,
                ],
            ],
        ];
    }

    public function getCompany(): Company
    {
        return new Company('Fake', 'fake', '850890666');
    }

    private function createTestObject(): HubspotCompanyManager
    {
        return new HubspotCompanyManager(
            $this->logger->reveal(),
            $this->hubspotCompanyRepository->reveal(),
            $this->companyRepository->reveal(),
            $this->hubspotClient->reveal(),
        );
    }
}
