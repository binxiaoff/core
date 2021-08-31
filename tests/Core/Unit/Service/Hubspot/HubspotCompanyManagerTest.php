<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Service\Hubspot;

use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyGroup;
use KLS\Core\Entity\CompanyStatus;
use KLS\Core\Entity\HubspotCompany;
use KLS\Core\Repository\CompanyRepository;
use KLS\Core\Repository\HubspotCompanyRepository;
use KLS\Core\Service\Hubspot\Client\HubspotClient;
use KLS\Core\Service\Hubspot\HubspotCompanyManager;
use KLS\Syndication\Agency\Repository\ProjectRepository as ProjectAgencyRepository;
use KLS\Syndication\Arrangement\Repository\ProjectRepository as ProjectArrangementRepository;
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
     * @covers ::synchronizeCompaniesToHubspot
     */
    public function testSynchronizeCompaniesToHubspotWithCompanyToCreate(): void
    {
        $response     = $this->prophesize(ResponseInterface::class);
        $arrCompanies = [
            $this->createCompany(),
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

        $result = $this->createTestObject()->synchronizeCompaniesToHubspot(10);
        static::assertArrayHasKey('companiesCreated', $result);
        static::assertArrayHasKey('companiesUpdated', $result);
    }

    /**
     * @covers ::synchronizeCompaniesToHubspot
     */
    public function testSynchronizeCompaniesToHubspotWithCompanyToCreateWithErrorReponse(): void
    {
        $response     = $this->prophesize(ResponseInterface::class);
        $arrCompanies = [
            $this->createCompany(),
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

        $result = $this->createTestObject()->synchronizeCompaniesToHubspot(10);
        static::assertArrayHasKey('companiesCreated', $result);
        static::assertArrayHasKey('companiesUpdated', $result);
    }

    public function testSynchronizeCompaniesToHubspotWithCompanyToUpdate(): void
    {
        $response     = $this->prophesize(ResponseInterface::class);
        $arrCompanies = [
            $this->createCompany(),
        ];

        $this->companyRepository->findCompaniesToCreateOnHubspot(10)->shouldBeCalledOnce()->willReturn(null);
        $this->companyRepository->findCompaniesToUpdateOnHubspot(10)->shouldBeCalledOnce()->willReturn($arrCompanies);

        $company = $this->createCompany();
        $this->hubspotCompanyRepository->findOneBy(['company' => $company])->shouldBeCalledOnce()->willReturn($company);

        $response->getStatusCode()->shouldBeCalled()->willReturn(Response::HTTP_OK);
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

        $this->hubspotClient->updateCompany('12344', $this->getFormatData())->shouldBeCalled()->willReturn($response->reveal());

        $this->hubspotCompanyRepository->persist(Argument::type(HubspotCompany::class))->shouldBeCalledOnce();
        $this->hubspotCompanyRepository->flush()->shouldBeCalled();

        $result = $this->createTestObject()->synchronizeCompaniesToHubspot(10);
        static::assertArrayHasKey('companiesCreated', $result);
        static::assertArrayHasKey('companiesUpdated', $result);
    }

    private function getFormatData(): array
    {
        $company = $this->createCompany();

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

    private function createCompany(): Company
    {
        $company      = new Company('displayName', 'CompanyName', 'siren');
        $companyGroup = new CompanyGroup('group');
        $status       = new CompanyStatus($company, 10);
        $company->setCurrentStatus($status);
        $company->setCompanyGroup($companyGroup);
        $company->setShortCode('KLS');
        $company->setEmailDomain('KLS');

        return $company;
    }

    public function getCompany(): Company
    {
        return new Company('Fake', 'fake', '850890666');
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
