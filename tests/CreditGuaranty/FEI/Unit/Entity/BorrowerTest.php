<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Entity;

use KLS\Core\Entity\Constant\CAInternalRating;
use KLS\Core\Entity\Constant\CAInternalRetailRating;
use KLS\Core\Entity\Constant\CARatingType;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReservationSetTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Entity\Borrower
 *
 * @internal
 */
class BorrowerTest extends TestCase
{
    use ReservationSetTrait;

    /**
     * @covers ::isGradeValid
     *
     * @dataProvider gradeProvider
     */
    public function testIsGradeValid(bool $expected, string $retailType, ?string $grade): void
    {
        $reservation = $this->createReservation();
        $this->withBorrower($reservation);
        $reservation->getProgram()->setRatingType($retailType);
        $reservation->getBorrower()->setGrade($grade);

        static::assertSame($expected, $reservation->getBorrower()->isGradeValid());
    }

    public function gradeProvider(): iterable
    {
        yield 'program and borrower with internal ratingType and grade' => [
            true,
            CARatingType::CA_INTERNAL_RATING,
            CAInternalRating::A,
        ];
        yield 'program and borrower with internal_retail ratingType and grade' => [
            true,
            CARatingType::CA_INTERNAL_RETAIL_RATING,
            CAInternalRetailRating::A,
        ];
        yield 'program with internal ratingType - borrower with grade null' => [
            false,
            CARatingType::CA_INTERNAL_RATING,
            null,
        ];
        yield 'program with internal ratingType - borrower with internal_retail grade' => [
            false,
            CARatingType::CA_INTERNAL_RATING,
            CAInternalRetailRating::V,
        ];
        yield 'program with internal_retail ratingType - borrower with internal grade' => [
            false,
            CARatingType::CA_INTERNAL_RETAIL_RATING,
            CAInternalRating::Z,
        ];
        yield 'program with non-existent ratingType - borrower with grade non-existent' => [
            false,
            'rating_type',
            'P',
        ];
        yield 'program with internal ratingType - borrower with grade non-existent' => [
            false,
            CARatingType::CA_INTERNAL_RATING,
            'P',
        ];
    }
}
