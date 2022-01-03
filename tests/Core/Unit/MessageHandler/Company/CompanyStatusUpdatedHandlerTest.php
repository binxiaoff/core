<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\MessageHandler\Company;

use Exception;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyStatus;
use KLS\Core\Message\Company\CompanyStatusUpdated;
use KLS\Core\MessageHandler\Company\CompanyStatusUpdatedHandler;
use KLS\Core\Repository\CompanyRepository;
use KLS\Core\Service\Notifier\CompanyStatus\CompanyHasSignedNotifier;
use KLS\Test\Core\Unit\Traits\CompanyTrait;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use Nexy\Slack\Exception\SlackApiException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass  \KLS\Core\MessageHandler\Company\CompanyStatusUpdatedHandler
 *
 * @internal
 */
class CompanyStatusUpdatedHandlerTest extends TestCase
{
    use CompanyTrait;
    use PropertyValueTrait;
    use ProphecyTrait;

    /** @var ObjectProphecy|CompanyRepository */
    private $companyRepository;

    /** @var ObjectProphecy|CompanyHasSignedNotifier */
    private $companySignedNotifier;

    protected function setUp(): void
    {
        $this->companyRepository     = $this->prophesize(CompanyRepository::class);
        $this->companySignedNotifier = $this->prophesize(CompanyHasSignedNotifier::class);
    }

    protected function tearDown(): void
    {
        $this->companyRepository     = null;
        $this->companySignedNotifier = null;
    }

    /**
     * @covers ::__invoke
     *
     * @dataProvider companyProvider
     *
     * @throws \Http\Client\Exception
     * @throws SlackApiException
     */
    public function testInvoke(?Company $company, CompanyStatusUpdated $companyStatusUpdated, bool $condition): void
    {
        $this->companyRepository->find($companyStatusUpdated->getCompanyId())->shouldBeCalledOnce()
            ->willReturn($company)
        ;

        if ($condition) {
            $this->companySignedNotifier->notify($company)->shouldBeCalledOnce();
        } else {
            $this->companySignedNotifier->notify($company)->shouldNotBeCalled();
        }

        $this->createTestObject()($companyStatusUpdated);
    }

    /**
     * @throws Exception
     */
    public function companyProvider(): iterable
    {
        $company = $this->createCompany();
        $this->forcePropertyValue($company, 'id', 1);

        $companyStatusProspect = new CompanyStatus($this->createCompany(), CompanyStatus::STATUS_PROSPECT);
        $companyStatusSigned   = new CompanyStatus($this->createCompany(), CompanyStatus::STATUS_SIGNED);
        $companyStatusRefused  = new CompanyStatus($this->createCompany(), CompanyStatus::STATUS_REFUSED);

        yield 'company-with-good-status' => [
            $company,
            new CompanyStatusUpdated($company, $companyStatusProspect, $companyStatusSigned),
            true,
        ];
        yield 'company-with-wrong-statuses' => [
            $company,
            new CompanyStatusUpdated($company, $companyStatusSigned, $companyStatusProspect),
            false,
        ];
        yield 'company-with-wrong-statuses2' => [
            $company,
            new CompanyStatusUpdated($company, $companyStatusRefused, $companyStatusSigned),
            false,
        ];
        yield 'company-no-longer-exists' => [
            null,
            new CompanyStatusUpdated($company, $companyStatusProspect, $companyStatusSigned),
            false,
        ];
    }

    private function createTestObject(): CompanyStatusUpdatedHandler
    {
        return new CompanyStatusUpdatedHandler(
            $this->companyRepository->reveal(),
            $this->companySignedNotifier->reveal()
        );
    }
}
