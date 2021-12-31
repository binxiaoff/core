<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\MessageHandler\CompanyModule;

use Http\Client\Exception;
use KLS\Core\Entity\CompanyModule;
use KLS\Core\Message\CompanyModule\CompanyModuleUpdated;
use KLS\Core\MessageHandler\CompanyModule\CompanyModuleUpdatedHandler;
use KLS\Core\Repository\CompanyModuleRepository;
use KLS\Core\Service\CompanyModule\CompanyModuleNotifier;
use KLS\Test\Core\Unit\Traits\CompanyTrait;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use Nexy\Slack\Exception\SlackApiException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionException;

/**
 * @coversDefaultClass  \KLS\Core\MessageHandler\CompanyModule\CompanyModuleUpdatedHandler
 *
 * @internal
 */
class CompanyModuleUpdatedHandlerTest extends TestCase
{
    use CompanyTrait;
    use PropertyValueTrait;
    use ProphecyTrait;

    /** @var CompanyModuleRepository|ObjectProphecy */
    private $companyModuleRepository;

    /** @var CompanyModuleNotifier|ObjectProphecy */
    private $companyModuleNotifier;

    protected function setUp(): void
    {
        $this->companyModuleRepository = $this->prophesize(CompanyModuleRepository::class);
        $this->companyModuleNotifier   = $this->prophesize(CompanyModuleNotifier::class);
    }

    protected function tearDown(): void
    {
        $this->companyModuleRepository = null;
        $this->companyModuleNotifier   = null;
    }

    /**
     * @covers ::__invoke
     *
     * @throws Exception
     * @throws SlackApiException
     * @throws ReflectionException
     */
    public function testInvoke(): void
    {
        $companyModule = new CompanyModule('code', $this->createCompany());
        $this->forcePropertyValue($companyModule, 'id', 1);
        $companyModuleUpdated = new CompanyModuleUpdated($companyModule);

        $this->companyModuleRepository->find($companyModule->getId())->shouldBeCalledOnce()
            ->willReturn($companyModule)
        ;

        $this->companyModuleNotifier->notifyModuleActivation($companyModule)->shouldBeCalledOnce();

        $this->createTestObject()($companyModuleUpdated);
    }

    /**
     * @covers ::__invoke
     *
     * @throws Exception
     * @throws SlackApiException
     * @throws ReflectionException
     */
    public function testInvokeWithNoModuleFound(): void
    {
        $companyModule = new CompanyModule('code', $this->createCompany());
        $this->forcePropertyValue($companyModule, 'id', 1);
        $companyModuleUpdated = new CompanyModuleUpdated($companyModule);

        $this->companyModuleRepository->find($companyModule->getId())->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $this->companyModuleNotifier->notifyModuleActivation(Argument::any())->shouldNotBeCalled();

        $this->createTestObject()($companyModuleUpdated);
    }

    private function createTestObject(): CompanyModuleUpdatedHandler
    {
        return new CompanyModuleUpdatedHandler(
            $this->companyModuleRepository->reveal(),
            $this->companyModuleNotifier->reveal()
        );
    }
}
