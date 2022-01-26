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
        array $ineligibles
    ): void {
        /** @var Staff $staff */
        $staff = static::getContainer()->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);

        $iri = \str_replace('{publicId}', $reservationPublicId, self::ENDPOINT);

        $response = $this->createAuthClient($staff)->request(Request::METHOD_GET, $iri);

        self::assertResponseStatusCodeSame(200);
        self::assertJsonContains(['ineligibles' => $ineligibles]);
    }

    public function reservationsProvider(): iterable
    {
        yield 'user-1 - reservation 1 : ineligible' => [
            'staff_company:basic_user-1',
            ReservationFixtures::RESERVATION_1,
            [
                'profile' => [
                    'young_farmer',
                    'subsidiary',
                    'turnover',
                ],
                'project' => [
                    'receiving_grant',
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
                    'land_value',
                ],
                'loan' => [],
            ],
        ];
        yield 'user-5 - reservation 2 : ineligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_2,
            [
                'project' => [
                    'total_fei_credit',
                ],
            ],
        ];
        yield 'user-11 - reservation 3 : eligible' => [
            'staff_company:basic_user-11',
            ReservationFixtures::RESERVATION_3,
            [],
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

        $iri = \str_replace('{publicId}', $reservationPublicId, self::ENDPOINT);

        $response = $this->createAuthClient($staff)->request($method, $iri);

        self::assertResponseStatusCodeSame(405);
    }

    public function notAllowedProvider(): iterable
    {
        foreach (ReservationFixtures::ALL_PROGRAM_COMMERCIALIZED_RESERVATIONS as $reservation) {
            yield 'user-2 - POST - ' . $reservation => [
                'staff_company:basic_user-2',
                $reservation,
                Request::METHOD_POST,
            ];
            yield 'user-3 - PATCH - ' . $reservation => [
                'staff_company:basic_user-3',
                $reservation,
                Request::METHOD_PATCH,
            ];
            yield 'user-4 - PUT - ' . $reservation => [
                'staff_company:basic_user-4',
                $reservation,
                Request::METHOD_PUT,
            ];
            yield 'user-6 - DELETE - ' . $reservation => [
                'staff_company:basic_user-6',
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
        string $reservationPublicId
    ): void {
        /** @var Staff $staff */
        $staff = static::getContainer()->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);

        $iri = \str_replace('{publicId}', $reservationPublicId, self::ENDPOINT);

        $response = $this->createAuthClient($staff)->request(Request::METHOD_GET, $iri);

        self::assertResponseStatusCodeSame(403);
    }

    public function forbiddenProvider(): iterable
    {
        foreach (ReservationFixtures::ALL_PROGRAM_COMMERCIALIZED_RESERVATIONS as $reservation) {
            yield 'user-a - ' . $reservation => [
                'staff_company:foo_user-a',
                $reservation,
            ];
            yield 'user-b - ' . $reservation => [
                'staff_company:foo_user-b',
                $reservation,
            ];
            yield 'user-c - ' . $reservation => [
                'staff_company:foo_user-c',
                $reservation,
            ];
            yield 'user-d - ' . $reservation => [
                'staff_company:foo_user-d',
                $reservation,
            ];
            yield 'user-e - ' . $reservation => [
                'staff_company:foo_user-e',
                $reservation,
            ];
        }
    }
}
