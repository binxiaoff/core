<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service;

use KLS\CreditGuaranty\FEI\Repository\FinancingObjectRepository;
use KLS\CreditGuaranty\FEI\Service\FinancingObjectUpdater;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReservationSetTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\FinancingObjectUpdater
 *
 * @internal
 */
class FinancingObjectUpdaterTest extends TestCase
{
    use ProphecyTrait;
    use ReservationSetTrait;

    /** @var ValidatorInterface|ObjectProphecy */
    private $validator;

    /** @var FinancingObjectRepository|ObjectProphecy */
    private $financingObjectRepository;

    protected function setUp(): void
    {
        $this->validator                 = $this->prophesize(ValidatorInterface::class);
        $this->financingObjectRepository = $this->prophesize(FinancingObjectRepository::class);
    }

    protected function tearDown(): void
    {
        $this->validator                 = null;
        $this->financingObjectRepository = null;
    }

    /**
     * @covers ::update
     *
     * @dataProvider arrayDataProvider
     */
    public function testSynchronize(array $data, array $error, array $response): void
    {
        $reservation     = $this->createReservation();
        $financingObject = $this->createFinancingObject($reservation, false);

        $this->validator->validate(Argument::type('array'), Argument::type(Constraint::class))
            ->shouldBeCalled()->willReturn($error);

        if ($response['notFoundFinancingObject'] > 0) {
            $this->financingObjectRepository->findOneBy(
                [
                    'loanNumber'      => 1,
                    'operationNumber' => 2,
                ]
            )->shouldBeCalledOnce()->willReturn(null);
        } elseif (0 === \count($error)) {
            $constraintViolation = $this->prophesize(ConstraintViolation::class);
            $constraintViolation->getCode()->shouldNotBeCalled();
            $constraintViolation->getMessage()->shouldNotBeCalled();
            $constraintViolation->getPropertyPath()->shouldNotBeCalled();

            $this->financingObjectRepository->findOneBy(
                [
                    'loanNumber'      => 1,
                    'operationNumber' => 2,
                ]
            )
                ->shouldBeCalledOnce()
                ->willReturn($financingObject)
            ;
        } else {
            $this->financingObjectRepository->findOneBy(
                [
                    'loanNumber'      => 1,
                    'operationNumber' => 2,
                ]
            )
                ->shouldNotBeCalled()
            ;
        }

        $this->financingObjectRepository->flush()->shouldBeCalledOnce();

        $dataReturn = $this->createTestObject()->update($data);

        static::assertCount($response['violations'], $dataReturn['violations']);
        static::assertCount($response['notFoundFinancingObject'], $dataReturn['notFoundFinancingObject']);
        static::assertSame($response['itemCount'], $dataReturn['itemCount']);
    }

    public function arrayDataProvider(): iterable
    {
        $constraintViolation = $this->prophesize(ConstraintViolation::class);
        $constraintViolation->getCode()->shouldBeCalled()->willReturn(Argument::type('string'));
        $constraintViolation->getMessage()->shouldBeCalled()->willReturn(Argument::type('string'));
        $constraintViolation->getPropertyPath()->shouldBeCalled()->willReturn(Argument::type('string'));

        yield 'synchronize-with-violations' => [
            [
                [
                    'n° GREEN'       => '1',
                    "n° d'opération" => '2',
                    'CRD'            => '1',
                    'Maturité'       => '3',
                    'TEST'           => 'fregfer',
                ],
                [
                    'n° GREENN'        => '2',
                    'n° d\'opérationn' => '2',
                    'CRD'              => '30404949',
                    'Maturité'         => '12',
                ],
            ],
            ['error' => $constraintViolation->reveal()],
            [
                'violations'              => 1,
                'notFoundFinancingObject' => 0,
                'itemCount'               => 2,
            ],
        ];
        yield 'synchronize-without-violation' => [
            [
                [
                    'n° GREEN'        => '1',
                    'n° d\'opération' => '2',
                    'CRD'             => '1',
                    'Maturité'        => '3',
                ],
            ],
            [],
            [
                'violations'              => 0,
                'notFoundFinancingObject' => 0,
                'itemCount'               => 1,
            ],
        ];
        yield 'synchronize-with-not-found-financing-object' => [
            [
                [
                    'n° GREEN'        => '1',
                    'n° d\'opération' => '2',
                    'CRD'             => '1',
                    'Maturité'        => '3',
                ],
            ],
            [],
            [
                'violations'              => 0,
                'notFoundFinancingObject' => 1,
                'itemCount'               => 1,
            ],
        ];
    }

    private function createTestObject(): FinancingObjectUpdater
    {
        return new FinancingObjectUpdater(
            $this->validator->reveal(),
            $this->financingObjectRepository->reveal()
        );
    }
}
