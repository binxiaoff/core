<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\Staff;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Faker\Provider\{Base, Internet};
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Unilend\Entity\{Clients, Companies, Staff};
use Unilend\Exception\{Client\ClientNotFoundException, Staff\StaffNotFoundException};
use Unilend\Repository\{ClientsRepository, CompaniesRepository, StaffRepository};
use Unilend\Service\{Company\CompanyManager, Staff\StaffManager};

/**
 * @internal
 *
 * @coversDefaultClass \Unilend\Service\Staff\StaffManager
 */
class StaffManagerTest extends TestCase
{
    /** @var CompanyManager|ObjectProphecy */
    private $companyManager;
    /** @var ClientsRepository|ObjectProphecy */
    private $clientsRepository;
    /** @var CompaniesRepository|ObjectProphecy */
    private $companiesRepository;
    /** @var StaffRepository|ObjectProphecy */
    private $staffRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->companyManager      = $this->prophesize(CompanyManager::class);
        $this->clientsRepository   = $this->prophesize(ClientsRepository::class);
        $this->companiesRepository = $this->prophesize(CompaniesRepository::class);
        $this->staffRepository     = $this->prophesize(StaffRepository::class);
    }

    /**
     * @covers ::getStaffByEmail
     *
     * @throws Exception
     */
    public function testGetStaffByEmail(): void
    {
        $email         = 'test@' . Internet::safeEmailDomain();
        $company       = new Companies('CALS', '850890666');
        $client        = new Clients($email);
        $expectedStaff = new Staff();

        $companyGetter = $this->companyManager->getCompanyByEmail(Argument::exact($email))->willReturn($company);
        $clientGetter  = $this->clientsRepository->findOneBy(Argument::exact(['email' => $email]))->willReturn($client);
        $staffGetter   = $this->staffRepository->findOneBy(Argument::exact(['company' => $company, 'client' => $client]))->willReturn($expectedStaff);

        $staff = $this->createTestObject()->getStaffByEmail($email);
        $companyGetter->shouldHaveBeenCalled();
        $clientGetter->shouldHaveBeenCalled();
        $staffGetter->shouldHaveBeenCalled();

        static::assertSame($expectedStaff, $staff);
    }

    /**
     * @covers ::getStaffByEmail
     *
     * @throws Exception
     */
    public function testGetStaffByEmailClientNotFound(): void
    {
        $this->expectException(ClientNotFoundException::class);

        $email = Internet::safeEmailDomain();
        $this->companyManager->getCompanyByEmail(Argument::exact($email))->willReturn(new Companies('CALS', '850890666'));
        $this->clientsRepository->findOneBy(Argument::exact(['email' => $email]))->willReturn(null);

        $this->createTestObject()->getStaffByEmail($email);
    }

    /**
     * @covers ::getStaffByEmail
     *
     * @throws Exception
     */
    public function testGetStaffByEmailStaffNotFound(): void
    {
        $this->expectException(StaffNotFoundException::class);

        $email   = 'test@' . Internet::safeEmailDomain();
        $company = new Companies('CALS', '850890666');
        $client  = new Clients($email);
        $company->setName(Base::lexify('?????????'));

        $this->companyManager->getCompanyByEmail(Argument::exact($email))->willReturn($company);
        $this->clientsRepository->findOneBy(Argument::exact(['email' => $email]))->willReturn($client);
        $this->staffRepository->findOneBy(Argument::exact(['company' => $company, 'client' => $client]))->willReturn(null);

        $this->createTestObject()->getStaffByEmail($email);
    }

    /**
     * @covers ::addStaffFromEmail
     *
     * @dataProvider clientProvider
     *
     * @param Clients|null $client
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testAddStaffFromEmail(?Clients $client): void
    {
        $email   = 'test@' . Internet::safeEmailDomain();
        $company = new Companies('CALS', '850890666');

        if ($client) {
            $client->setEmail($email);
        }

        $companyGetter = $this->companyManager->getCompanyByEmail(Argument::exact($email))->willReturn($company);
        $clientGetter  = $this->clientsRepository->findOneBy(Argument::exact(['email' => $email]))->willReturn($client);
        $clientSaver   = $this->clientsRepository->save(Argument::type(Clients::class));

        $staff = $this->createTestObject()->addStaffFromEmail($email);

        $companyGetter->shouldHaveBeenCalled();
        $clientGetter->shouldHaveBeenCalled();

        if (null === $client) {
            $clientSaver->shouldHaveBeenCalled();
        }

        $this->companiesRepository->save(Argument::exact($company))->shouldHaveBeenCalled();

        static::assertSame($company, $staff->getCompany());
        static::assertSame($email, $staff->getClient()->getEmail());
        static::assertSame([Staff::DUTY_STAFF_OPERATOR], $staff->getRoles());
    }

    /**
     * @return array
     *
     * @throws Exception
     */
    public function clientProvider(): array
    {
        return [
            [null],
            [new Clients('test@' . Internet::safeEmailDomain())],
        ];
    }

    /**
     * @return array
     */
    public function marketSegmentProvider(): array
    {
        return [
            ['public_collectivity'],
            ['energy'],
            ['corporate'],
            ['lbo'],
            ['real_estate_development'],
            ['infrastructure'],
        ];
    }

    /**
     * @return StaffManager
     */
    private function createTestObject(): StaffManager
    {
        return new StaffManager(
            $this->companyManager->reveal(),
            $this->clientsRepository->reveal(),
            $this->companiesRepository->reveal(),
            $this->staffRepository->reveal()
        );
    }
}
