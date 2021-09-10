<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Service\Hubspot;

use KLS\Core\Entity\Company;
use KLS\Core\Entity\HubspotCompany;
use KLS\Core\Repository\CompanyRepository;
use KLS\Core\Repository\HubspotCompanyRepository;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Service\Hubspot\Client\HubspotClient;
use KLS\Core\Service\Hubspot\HubspotCompanyManager;
use KLS\Syndication\Agency\Repository\ProjectRepository as ProjectAgencyRepository;
use KLS\Syndication\Arrangement\Repository\ProjectRepository as ProjectArrangementRepository;
use KLS\Test\Core\Unit\Traits\CompanyTrait;
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
    use CompanyTrait;

    /** @var ObjectProphecy|LoggerInterface */
    private $logger;

    /** @var HubspotCompanyRepository|ObjectProphecy */
    private $hubspotCompanyRepository;

    /** @var CompanyRepository|ObjectProphecy */
    private $companyRepository;

    /** @var HubspotClient|ObjectProphecy */
    private $hubspotClient;

    /** @var ProjectAgencyRepository|ObjectProphecy */
    private $projectAgencyRepository;

    /** @var ProjectArrangementRepository|ObjectProphecy */
    private $projectArrangementRepository;

    /** @var UserRepository|ObjectProphecy */
    private $userRepository;

    protected function setUp(): void
    {
        $this->companyRepository            = $this->prophesize(CompanyRepository::class);
        $this->hubspotCompanyRepository     = $this->prophesize(HubspotCompanyRepository::class);
        $this->hubspotClient                = $this->prophesize(HubspotClient::class);
        $this->userRepository               = $this->prophesize(UserRepository::class);
        $this->projectAgencyRepository      = $this->prophesize(ProjectAgencyRepository::class);
        $this->projectArrangementRepository = $this->prophesize(ProjectArrangementRepository::class);
        $this->logger                       = $this->prophesize(LoggerInterface::class);
    }

    protected function tearDown(): void
    {
        $this->companyRepository            = null;
        $this->hubspotCompanyRepository     = null;
        $this->hubspotClient                = null;
        $this->userRepository               = null;
        $this->projectAgencyRepository      = null;
        $this->projectArrangementRepository = null;
        $this->logger                       = null;
    }

    /**
     * @covers ::importCompaniesFromHubspot
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

        $result = $this->createTestObject()->importCompaniesFromHubspot(0);

        static::assertArrayHasKey('lastCompanyId', $result);
        static::assertArrayHasKey('companyAddedNb', $result);
    }

    /**
     * @covers ::importCompaniesFromHubspot
     */
    public function testSynchronizeWithHubspotCompaniesAlreadyExisted(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $this->hubspotClient->fetchAllCompanies(0)->shouldBeCalledOnce()->willReturn($response->reveal());
        $response->getStatusCode()->willReturn(Response::HTTP_OK);
        $response->getContent()->willReturn(\json_encode($this->getHubspotCompanyCreatedResponse(), JSON_THROW_ON_ERROR));

        $this->hubspotCompanyRepository->findOneBy(['hubspotCompanyId' => '6584949386'])->shouldBeCalled()
            ->willReturn(new HubspotCompany($this->createCompany(), '6584949386'))
        ;
        $this->companyRepository->findOneBy(['shortCode' => Argument::any()])->shouldNotBeCalled();

        $this->hubspotCompanyRepository->flush()->shouldBeCalled();

        $result = $this->createTestObject()->importCompaniesFromHubspot(0);

        static::assertArrayHasKey('lastCompanyId', $result);
        static::assertArrayHasKey('companyAddedNb', $result);
    }

    /**
     * @covers ::importCompaniesFromHubspot
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

        $result = $this->createTestObject()->importCompaniesFromHubspot(0);

        static::assertArrayHasKey('lastCompanyId', $result);
        static::assertArrayHasKey('companyAddedNb', $result);
        static::assertSame(0, $result['lastCompanyId']);
        static::assertSame(0, $result['companyAddedNb']);
    }

    /**
     * @covers ::importCompaniesFromHubspot
     */
    public function testSynchronizeHubspotCompanies(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $this->hubspotClient->fetchAllCompanies(0)->shouldBeCalledOnce()->willReturn($response->reveal());
        $response->getStatusCode()->willReturn(Response::HTTP_OK);
        $response->getContent()->willReturn(\json_encode($this->getHubspotCompanyCreatedResponse(), JSON_THROW_ON_ERROR));

        $this->hubspotCompanyRepository->findOneBy(['hubspotCompanyId' => '6584949386'])->shouldBeCalled()->willReturn(null);
        $this->companyRepository->findOneBy(['shortCode' => 'KLS'])->shouldBeCalledOnce()->willReturn($this->createCompany());

        $this->hubspotCompanyRepository->persist(Argument::type(HubspotCompany::class));
        $this->hubspotCompanyRepository->flush()->shouldBeCalled();

        $result = $this->createTestObject()->importCompaniesFromHubspot(0);

        static::assertArrayHasKey('lastCompanyId', $result);
        static::assertArrayHasKey('companyAddedNb', $result);
        static::assertSame(0, $result['lastCompanyId']);
        static::assertSame(1, $result['companyAddedNb']);
    }

    /**
     * @covers ::exportCompaniesToHubspot
     */
    public function testSynchronizeCompaniesToHubspotWithCompanyToCreate(): void
    {
        $response     = $this->prophesize(ResponseInterface::class);
        $arrCompanies = [
            $this->createCompanyWithGroupAndStatus(),
        ];

        $this->companyRepository->findCompaniesToCreateOnHubspot(10)->shouldBeCalledOnce()->willReturn($arrCompanies);
        $this->companyRepository->findCompaniesToUpdateOnHubspot(10)->shouldBeCalledOnce()->willReturn(null);

        $response->getStatusCode()->shouldBeCalled()->willReturn(Response::HTTP_CREATED);
        $response->getContent()->shouldBeCalled()->willReturn(\json_encode([
            'id'         => '4802581745',
            'properties' => [
                'createdate'     => '2021-08-31T16:11:09.412Z',
                'domain'         => 'test',
                'kls_short_code' => 'fzefez',
                'name'           => 'Cambridge',
            ],
            'createdAt' => '2021-08-31T16:11:09.412Z',
            'updatedAt' => '2021-08-31T16:11:09.412Z',
            'archived'  => false,
        ], JSON_THROW_ON_ERROR));
        $this->userRepository->findActiveUsersPerCompany(Argument::type(Company::class))->shouldBeCalled()->willReturn(['user_init_percentage' => 86]);
        $this->projectAgencyRepository->countProjectsByCompany(Argument::type(Company::class))->shouldBeCalledOnce()->willReturn(11);
        $this->projectArrangementRepository->countProjectsByCompany(Argument::type(Company::class))->shouldBeCalledOnce()->willReturn(11);

        $this->hubspotClient->postNewCompany($this->getFormatData())->shouldBeCalled()->willReturn($response->reveal());

        $this->hubspotCompanyRepository->persist(Argument::type(HubspotCompany::class))->shouldBeCalledOnce();
        $this->hubspotCompanyRepository->flush()->shouldBeCalled();

        $result = $this->createTestObject()->exportCompaniesToHubspot(10);

        static::assertArrayHasKey('companiesCreated', $result);
        static::assertArrayHasKey('companiesUpdated', $result);
        static::assertSame(0, $result['companiesUpdated']);
        static::assertSame(1, $result['companiesCreated']);
    }

    /**
     * @covers ::exportCompaniesToHubspot
     */
    public function testSynchronizeCompaniesToHubspotWithCompanyToCreateWithErrorReponse(): void
    {
        $response     = $this->prophesize(ResponseInterface::class);
        $arrCompanies = [
            $this->createCompanyWithGroupAndStatus(),
        ];

        $this->companyRepository->findCompaniesToCreateOnHubspot(10)->shouldBeCalledOnce()->willReturn($arrCompanies);
        $this->companyRepository->findCompaniesToUpdateOnHubspot(10)->shouldBeCalledOnce()->willReturn(null);

        $response->getStatusCode()->shouldBeCalled()->willReturn(Response::HTTP_INTERNAL_SERVER_ERROR);
        $response->getContent(false)->shouldBeCalledOnce();

        $this->userRepository->findActiveUsersPerCompany(Argument::type(Company::class))->shouldBeCalled()->willReturn(['user_init_percentage' => 86]);
        $this->projectAgencyRepository->countProjectsByCompany(Argument::type(Company::class))->shouldBeCalledOnce()->willReturn(11);
        $this->projectArrangementRepository->countProjectsByCompany(Argument::type(Company::class))->shouldBeCalledOnce()->willReturn(11);

        $this->hubspotClient->postNewCompany($this->getFormatData())->shouldBeCalled()->willReturn($response->reveal());

        $this->hubspotCompanyRepository->persist(Argument::type(HubspotCompany::class))->shouldNotBeCalled();
        $this->hubspotCompanyRepository->flush()->shouldBeCalled();

        $result = $this->createTestObject()->exportCompaniesToHubspot(10);
        static::assertArrayHasKey('companiesCreated', $result);
        static::assertArrayHasKey('companiesUpdated', $result);
    }

    /**
     * @covers ::exportCompaniesToHubspot
     */
    public function testSynchronizeCompaniesToHubspotWithCompanyToUpdate(): void
    {
        $response     = $this->prophesize(ResponseInterface::class);
        $arrCompanies = [
            $this->createCompanyWithGroupAndStatus(),
        ];

        $this->companyRepository->findCompaniesToCreateOnHubspot(11)->shouldBeCalledOnce()->willReturn(null);
        $this->companyRepository->findCompaniesToUpdateOnHubspot(11)->shouldBeCalledOnce()->willReturn($arrCompanies);

        $hubspotCompany = new HubspotCompany($arrCompanies[0], '4802581745');
        $this->hubspotCompanyRepository->findOneBy(['company' => $arrCompanies[0]])->shouldBeCalledOnce()->willReturn($hubspotCompany);

        $response->getStatusCode()->shouldBeCalled()->willReturn(Response::HTTP_OK);
        $response->getContent()->shouldNotBeCalled();

        $this->userRepository->findActiveUsersPerCompany(Argument::type(Company::class))->shouldBeCalled()->willReturn(['user_init_percentage' => 86]);
        $this->projectAgencyRepository->countProjectsByCompany(Argument::type(Company::class))->shouldBeCalledOnce()->willReturn(11);
        $this->projectArrangementRepository->countProjectsByCompany(Argument::type(Company::class))->shouldBeCalledOnce()->willReturn(11);

        $this->hubspotClient->updateCompany('4802581745', $this->getFormatData())->shouldBeCalledOnce()->willReturn($response->reveal());

        $this->hubspotCompanyRepository->flush()->shouldBeCalledOnce();

        $result = $this->createTestObject()->exportCompaniesToHubspot(11);

        static::assertArrayHasKey('companiesCreated', $result);
        static::assertArrayHasKey('companiesUpdated', $result);
        static::assertSame(0, $result['companiesCreated']);
        static::assertSame(1, $result['companiesUpdated']);
    }

    /**
     * @covers ::exportCompaniesToHubspot
     */
    public function testSynchronizeCompaniesToHubspotWithCompanyToUpdateWithErrorResponse(): void
    {
        $response     = $this->prophesize(ResponseInterface::class);
        $arrCompanies = [
            $this->createCompanyWithGroupAndStatus(),
        ];

        $this->companyRepository->findCompaniesToCreateOnHubspot(11)->shouldBeCalledOnce()->willReturn(null);
        $this->companyRepository->findCompaniesToUpdateOnHubspot(11)->shouldBeCalledOnce()->willReturn($arrCompanies);

        $hubspotCompany = new HubspotCompany($arrCompanies[0], '4802581745');
        $this->hubspotCompanyRepository->findOneBy(['company' => $arrCompanies[0]])->shouldBeCalledOnce()->willReturn($hubspotCompany);

        $response->getStatusCode()->shouldBeCalled()->willReturn(Response::HTTP_INTERNAL_SERVER_ERROR);
        $response->getContent(false)->shouldBeCalled();

        $this->userRepository->findActiveUsersPerCompany(Argument::type(Company::class))->shouldBeCalled()->willReturn(['user_init_percentage' => 86]);
        $this->projectAgencyRepository->countProjectsByCompany(Argument::type(Company::class))->shouldBeCalledOnce()->willReturn(11);
        $this->projectArrangementRepository->countProjectsByCompany(Argument::type(Company::class))->shouldBeCalledOnce()->willReturn(11);

        $this->hubspotClient->updateCompany('4802581745', $this->getFormatData())->shouldBeCalledOnce()->willReturn($response->reveal());

        $this->hubspotCompanyRepository->flush()->shouldBeCalledOnce();

        $result = $this->createTestObject()->exportCompaniesToHubspot(11);
        static::assertArrayHasKey('companiesCreated', $result);
        static::assertArrayHasKey('companiesUpdated', $result);
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

    private function getFormatData(): array
    {
        $company = $this->createCompanyWithGroupAndStatus();

        return [
            'properties' => [
                'name'                     => $company->getDisplayName(),
                'domain'                   => $company->getEmailDomain(),
                'kls_short_code'           => $company->getShortCode(),
                'kls_bank_group'           => 'group',
                'kls_company_status'       => 'Signé',
                'kls_user_init_percentage' => null,
                'kls_active_modules'       => '',
                'kls_agency_projects'      => 11,
                'kls_arrangement_projects' => 11,
            ],
        ];
    }

    private function createTestObject(): HubspotCompanyManager
    {
        return new HubspotCompanyManager(
            $this->companyRepository->reveal(),
            $this->hubspotCompanyRepository->reveal(),
            $this->hubspotClient->reveal(),
            $this->userRepository->reveal(),
            $this->projectAgencyRepository->reveal(),
            $this->projectArrangementRepository->reveal(),
            $this->logger->reveal(),
        );
    }
}
