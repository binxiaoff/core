<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Functional\Entity\Reservation;

use KLS\Core\Entity\Staff;
use KLS\Core\Repository\StaffRepository;
use KLS\Test\Core\Functional\Api\AbstractApiTest;
use KLS\Test\CreditGuaranty\FEI\DataFixtures\ReservationFixtures;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversNothing
 *
 * @internal
 */
class IneligibilitiesTest extends AbstractApiTest
{
    private const ENDPOINT = '/credit_guaranty/reservations/{publicId}/ineligibilities';

    protected function setUp(): void
    {
        self::bootKernel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @dataProvider reservationsProvider
     */
    public function testGetReservationIneligibilities(
        string $staffPublicId,
        string $reservationPublicId,
        bool $withConditions,
        array $ineligibles,
        ?string $category = null
    ): void {
        /** @var Staff $staff */
        $staff = static::getContainer()->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);

        $condition = (string) $withConditions ? 1 : 0;

        $params = "?withConditions={$condition}";
        $params .= empty($category) ? '' : "&category={$category}";

        $iri = \str_replace(
            '{publicId}',
            $reservationPublicId,
            self::ENDPOINT
        ) . $params;

        $response = $this->createAuthClient($staff)->request(Request::METHOD_GET, $iri);

        self::assertResponseStatusCodeSame(200);
        self::assertJsonContains(['ineligibles' => $ineligibles]);
    }

    public function reservationsProvider(): iterable
    {
        yield 'user-1 - reservation 1 - checking without conditions : ineligible' => [
            'staff_company:basic_user-1',
            ReservationFixtures::RESERVATION_1,
            false,
            [
                'profile' => [
                    'young_farmer',
                    'subsidiary',
                ],
                'project' => [
                    'investment_street',
                    'investment_post_code',
                    'investment_city',
                    'investment_department',
                    'investment_country',
                    'investment_thematic',
                    'investment_type',
                    'aid_intensity',
                    'additional_guaranty',
                    'agricultural_branch',
                    'project_contribution',
                    'eligible_fei_credit',
                    'total_fei_credit',
                    'tangible_fei_credit',
                    'intangible_fei_credit',
                    'credit_excluding_fei',
                    'project_grant',
                    'land_value',
                ],
                'loan' => [],
            ],
            null,
        ];
        yield 'user-1 - reservation 1 - checking with conditions : ineligible' => [
            'staff_company:basic_user-1',
            ReservationFixtures::RESERVATION_1,
            true,
            [
                'profile' => [
                    'young_farmer',
                    'subsidiary',
                    'turnover',
                ],
                'project' => [
                    'investment_street',
                    'investment_post_code',
                    'investment_city',
                    'investment_department',
                    'investment_country',
                    'investment_thematic',
                    'investment_type',
                    'aid_intensity',
                    'additional_guaranty',
                    'agricultural_branch',
                    'project_contribution',
                    'eligible_fei_credit',
                    'total_fei_credit',
                    'tangible_fei_credit',
                    'intangible_fei_credit',
                    'credit_excluding_fei',
                    'project_grant',
                    'land_value',
                ],
                'loan' => [],
            ],
            null,
        ];
        yield 'user-2 - reservation 2 - checking profile without conditions : eligible' => [
            'staff_company:basic_user-2',
            ReservationFixtures::RESERVATION_2,
            false,
            [],
            'profile',
        ];
        yield 'user-2 - reservation 2 - checking profile with conditions : eligible' => [
            'staff_company:basic_user-2',
            ReservationFixtures::RESERVATION_2,
            true,
            [],
            'profile',
        ];
        yield 'user-3 - reservation 2 - checking project without conditions : eligible' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_2,
            false,
            [],
            'project',
        ];
        yield 'user-3 - reservation 2 - checking project with conditions : ineligible' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_2,
            true,
            ['project' => ['total_fei_credit']],
            'project',
        ];
        yield 'user-4 - reservation 2 - checking loan without conditions : eligible' => [
            'staff_company:basic_user-4',
            ReservationFixtures::RESERVATION_2,
            false,
            [],
            'loan',
        ];
        yield 'user-4 - reservation 2 - checking loan with conditions : eligible' => [
            'staff_company:basic_user-4',
            ReservationFixtures::RESERVATION_2,
            true,
            [],
            'loan',
        ];
        yield 'user-5 - reservation 2 - checking without conditions : eligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_2,
            false,
            [],
            null,
        ];
        yield 'user-5 - reservation 2 - checking with conditions : ineligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_2,
            true,
            ['project' => ['total_fei_credit']],
            null,
        ];
        yield 'user-11 - reservation 3 - checking with conditions : eligible' => [
            'staff_company:basic_user-11',
            ReservationFixtures::RESERVATION_3,
            true,
            [],
            null,
        ];
    }

    /**
     * @dataProvider notAllowedProvider
     */
    public function testReservationIneligibilitiesNotAllowed(
        string $staffPublicId,
        string $reservationPublicId,
        string $method
    ): void {
        /** @var Staff $staff */
        $staff = static::getContainer()->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);

        $iri = \str_replace(
            '{publicId}',
            $reservationPublicId,
            self::ENDPOINT
        );

        $response = $this->createAuthClient($staff)
            ->request($method, $iri)
        ;

        self::assertResponseStatusCodeSame(405);
    }

    public function notAllowedProvider(): iterable
    {
        foreach (ReservationFixtures::ALL_PROGRAM_COMMERCIALIZED_RESERVATIONS as $reservation) {
            yield 'user-1 - POST - ' . $reservation => [
                'staff_company:basic_user-1',
                $reservation,
                Request::METHOD_POST,
            ];
            yield 'user-2 - PATCH - ' . $reservation => [
                'staff_company:basic_user-2',
                $reservation,
                Request::METHOD_PATCH,
            ];
            yield 'user-2 - PUT - ' . $reservation => [
                'staff_company:basic_user-2',
                $reservation,
                Request::METHOD_PUT,
            ];
            yield 'user-3 - DELETE - ' . $reservation => [
                'staff_company:basic_user-3',
                $reservation,
                Request::METHOD_DELETE,
            ];
        }
    }

    /**
     * @dataProvider forbiddenProvider
     */
    public function testGetReservationIneligibilitiesForbidden(
        string $staffPublicId,
        string $reservationPublicId,
        bool $withConditions,
        ?string $category = null
    ): void {
        /** @var Staff $staff */
        $staff = static::getContainer()->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);

        $condition = $withConditions ? 1 : 0;

        $params = "?withConditions={$condition}";
        $params .= empty($category) ? '' : "&category={$category}";

        $iri = \str_replace(
            '{publicId}',
            $reservationPublicId,
            self::ENDPOINT
        ) . $params;

        $response = $this->createAuthClient($staff)
            ->request(Request::METHOD_GET, $iri)
        ;

        self::assertResponseStatusCodeSame(403);
    }

    public function forbiddenProvider(): iterable
    {
        foreach (ReservationFixtures::ALL_PROGRAM_COMMERCIALIZED_RESERVATIONS as $reservation) {
            yield 'user-a - ' . $reservation => [
                'staff_company:foo_user-a',
                $reservation,
                false,
                'profile',
            ];
            yield 'user-b - ' . $reservation => [
                'staff_company:foo_user-b',
                $reservation,
                true,
                'profile',
            ];
            yield 'user-c - ' . $reservation => [
                'staff_company:foo_user-c',
                $reservation,
                false,
                'project',
            ];
            yield 'user-d - ' . $reservation => [
                'staff_company:foo_user-d',
                $reservation,
                true,
                'loan',
            ];
            yield 'user-e - ' . $reservation => [
                'staff_company:foo_user-e',
                $reservation,
                false,
                null,
            ];
        }
    }
}
