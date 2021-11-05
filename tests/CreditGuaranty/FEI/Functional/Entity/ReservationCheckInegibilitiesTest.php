<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Functional\Entity;

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
class ReservationCheckInegibilitiesTest extends AbstractApiTest
{
    private const ENDPOINT_RESERVATION_INELIGIBILITIES = '/credit_guaranty/reservations/{publicId}/ineligibilities';

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
            self::ENDPOINT_RESERVATION_INELIGIBILITIES
        ) . $params;

        $response = $this->createAuthClient($staff)->request(Request::METHOD_GET, $iri);

        self::assertResponseStatusCodeSame(200);
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
            'profile',
        ];
        yield 'user-2 - reservation sent 1 - checking profile without conditions : eligible' => [
            'staff_company:basic_user-2',
            ReservationFixtures::RESERVATION_SENT_1,
            false,
            [],
            'profile',
        ];
        yield 'user-2 - reservation sent 1 - checking profile with conditions : eligible' => [
            'staff_company:basic_user-2',
            ReservationFixtures::RESERVATION_SENT_1,
            true,
            [],
            'profile',
        ];
        yield 'user-3 - reservation sent 1 - checking project without conditions : eligible' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_SENT_1,
            false,
            [],
            'project',
        ];
        yield 'user-3 - reservation sent 1 - checking project with conditions : ineligible' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_SENT_1,
            true,
            [
                'project' => [
                    'investment_thematic',
                    'total_fei_credit',
                ],
            ],
            'project',
        ];
        yield 'user-5 - reservation sent 1 - checking loan without conditions : eligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_SENT_1,
            false,
            [],
            'loan',
        ];
        yield 'user-5 - reservation sent 1 - checking loan with conditions : eligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_SENT_1,
            true,
            [],
            'loan',
        ];
        yield 'user-11 - reservation sent 1 - checking conditions : ineligible' => [
            'staff_company:basic_user-11',
            ReservationFixtures::RESERVATION_SENT_1,
            true,
            [],
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
            'profile',
        ];
        yield 'user-4 - reservation sent 2 - checking project without conditions : ineligible' => [
            'staff_company:basic_user-4',
            ReservationFixtures::RESERVATION_SENT_2,
            false,
            [
                'project' => [
                    'investment_thematic',
                    'project_grant',
                ],
            ],
            'project',
        ];
        yield 'user-4 - reservation sent 2 - checking project with conditions : ineligible' => [
            'staff_company:basic_user-4',
            ReservationFixtures::RESERVATION_SENT_2,
            true,
            [
                'project' => [
                    'investment_thematic',
                    'project_grant',
                ],
            ],
            'project',
        ];
        yield 'user-5 - reservation sent 2 - checking loan without conditions : eligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_SENT_2,
            false,
            [],
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
            'loan',
        ];
        yield 'user-11 - reservation sent 2 - checking conditions : ineligible' => [
            'staff_company:basic_user-11',
            ReservationFixtures::RESERVATION_SENT_2,
            true,
            [
                'profile' => [
                    'young_farmer',
                    'creation_in_progress',
                ],
                'project' => [
                    'investment_thematic',
                    'project_grant',
                ],
            ],
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
            self::ENDPOINT_RESERVATION_INELIGIBILITIES
        );

        $response = $this->createAuthClient($staff)
            ->request($method, $iri)
        ;

        self::assertResponseStatusCodeSame(405);
    }

    public function notAllowedProvider(): iterable
    {
        foreach ([ReservationFixtures::RESERVATION_DRAFT_1, ReservationFixtures::RESERVATION_DRAFT_2] as $reservation) {
            yield 'user-1 - ' . $reservation => [
                'staff_company:basic_user-1',
                $reservation,
                Request::METHOD_POST,
            ];
            yield 'user-2 - ' . $reservation => [
                'staff_company:basic_user-2',
                $reservation,
                Request::METHOD_PATCH,
            ];
            yield 'user-3 - ' . $reservation => [
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
            self::ENDPOINT_RESERVATION_INELIGIBILITIES
        ) . $params;

        $response = $this->createAuthClient($staff)
            ->request(Request::METHOD_GET, $iri)
        ;

        self::assertResponseStatusCodeSame(403);
    }

    public function forbiddenProvider(): iterable
    {
        foreach ([ReservationFixtures::RESERVATION_DRAFT_1, ReservationFixtures::RESERVATION_DRAFT_2] as $reservation) {
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
