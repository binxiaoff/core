<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Functional\Entity;

use KLS\Core\Entity\Staff;
use KLS\Core\Repository\StaffRepository;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\Test\Core\Functional\Api\AbstractApiTest;
use KLS\Test\CreditGuaranty\FEI\DataFixtures\ReservationFixtures;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversNothing
 *
 * @internal
 */
class ReservationCheckInegibilitiesTest extends AbstractApiTest
{
    private const ENDPOINT_RESERVATION_ELIGIBILITY_CHECKING = '/credit_guaranty/reservations/{publicId}/ineligibilities';

    protected function setUp(): void
    {
        self::bootKernel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @dataProvider successfullProvider
     */
    public function testPostEligibilitiesChecking(
        string $staffPublicId,
        string $reservationPublicId,
        bool $withConditions,
        array $ineligibles,
        int $statusCode,
        ?string $category = null
    ): void {
        /** @var Staff $staff */
        $staff = static::getContainer()->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);
        /** @var Reservation $reservation */
        $condition = $withConditions ? 1 : 0;

        $iri = \str_replace(
            '{publicId}',
            $reservationPublicId,
            self::ENDPOINT_RESERVATION_ELIGIBILITY_CHECKING
        )
            . "?category={$category}&withConditions={$condition}";

        $response = $this->createAuthClient($staff)->request(Request::METHOD_GET, $iri);

        self::assertResponseStatusCodeSame($statusCode);
        self::assertJsonContains(['ineligibles' => $ineligibles]);
    }

    public function successfullProvider(): iterable
    {
        yield 'user-1 - reservation draft 2 - checking profile without conditions : ineligible' => [
            'staff_company:basic_user-1',
            ReservationFixtures::RESERVATION_DRAFT_2,
            false,
            [
                'profile' => [
                    'young_farmer',
                    'subsidiary',
                ],
            ],
            403,
            'profile',
        ];
        yield 'user-2 - reservation sent 1 - checking profile without conditions : eligible' => [
            'staff_company:basic_user-2',
            ReservationFixtures::RESERVATION_SENT_1,
            false,
            [],
            200,
            'profile',
        ];
        yield 'user-2 - reservation sent 1 - checking profile with conditions : eligible' => [
            'staff_company:basic_user-2',
            ReservationFixtures::RESERVATION_SENT_1,
            true,
            [],
            200,
            'profile',
        ];
        yield 'user-3 - reservation sent 1 - checking project without conditions : eligible' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_SENT_1,
            false,
            [],
            200,
            'project',
        ];
        yield 'user-3 - reservation sent 1 - checking project with conditions : ineligible' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_SENT_1,
            true,
            [
                'project' => [
                    'total_fei_credit',
                ],
            ],
            403,
            'project',
        ];
        yield 'user-5 - reservation sent 1 - checking loan without conditions : eligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_SENT_1,
            false,
            [],
            200,
            'loan',
        ];
        yield 'user-5 - reservation sent 1 - checking loan with conditions : eligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_SENT_1,
            true,
            [],
            200,
            'loan',
        ];
        yield 'user-11 - reservation sent 1 - checking conditions : eligible' => [
            'staff_company:basic_user-11',
            ReservationFixtures::RESERVATION_SENT_1,
            true,
            [],
            200,
            null,
        ];
        yield 'user-3 - reservation sent 2 - checking profile without conditions : ineligible' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_SENT_2,
            false,
            [
                'profile' => [
                    'young_farmer',
                    'creation_in_progress',
                ],
            ],
            403,
            'profile',
        ];
        yield 'user-3 - reservation sent 2 - checking profile with conditions : ineligible' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_SENT_2,
            true,
            [
                'profile' => [
                    'young_farmer',
                    'creation_in_progress',
                ],
            ],
            403,
            'profile',
        ];
        yield 'user-4 - reservation sent 2 - checking project without conditions : ineligible' => [
            'staff_company:basic_user-4',
            ReservationFixtures::RESERVATION_SENT_2,
            false,
            [
                'project' => [
                    'project_grant',
                ],
            ],
            403,
            'project',
        ];
        yield 'user-4 - reservation sent 2 - checking project with conditions : ineligible' => [
            'staff_company:basic_user-4',
            ReservationFixtures::RESERVATION_SENT_2,
            true,
            [
                'project' => [
                    'project_grant',
                ],
            ],
            403,
            'project',
        ];
        yield 'user-5 - reservation sent 2 - checking loan without conditions : eligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_SENT_2,
            false,
            [],
            200,
            'loan',
        ];
        yield 'user-5 - reservation sent 2 - checking loan with conditions : ineligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_SENT_2,
            true,
            [
                'loan' => [
                    'loan_duration',
                ],
            ],
            403,
            'loan',
        ];
        yield 'user-11 - reservation sent 2 - checking conditions : ineligible' => [
            'staff_company:basic_user-11',
            ReservationFixtures::RESERVATION_SENT_2,
            true,
            [
                'loan' => [
                    'loan_duration',
                ],
            ],
            403,
            'loan',
        ];
    }

    /**
     * @dataProvider forbiddenProvider
     */
    public function testPostEligibilitiesCheckingForbidden(
        string $staffPublicId,
        string $reservationPublicId,
        bool $withConditions,
        int $statusCode,
        ?string $category = null
    ): void {
        /** @var Staff $staff */
        $staff     = static::getContainer()->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);
        $condition = $withConditions ? 1 : 0;

        $iri = \str_replace(
            '{publicId}',
            $reservationPublicId,
            self::ENDPOINT_RESERVATION_ELIGIBILITY_CHECKING
        )
            . "?category={$category}&withConditions={$condition}";

        $response = $this->createAuthClient($staff)
            ->request(Request::METHOD_POST, $iri)
        ;

        self::assertResponseStatusCodeSame($statusCode);
    }

    public function forbiddenProvider(): iterable
    {
        yield 'user-a' => [
            'staff_company:basic_user-1',
            ReservationFixtures::RESERVATION_DRAFT_2,
            false,
            405,
            'profile',
        ];
    }
}
