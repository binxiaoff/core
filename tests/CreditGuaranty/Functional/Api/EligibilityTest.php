<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\Functional\Api;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Repository\StaffRepository;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Repository\ReservationRepository;
use Unilend\Test\Core\Functional\Api\AbstractApiTest;
use Unilend\Test\CreditGuaranty\DataFixtures\ReservationFixtures;

/**
 * @coversNothing
 *
 * @internal
 */
class EligibilityTest extends AbstractApiTest
{
    private const ENDPOINT_ELIGIBILITY          = '/credit_guaranty/eligibilities/';
    private const ENDPOINT_ELIGIBILITY_CHECKING = self::ENDPOINT_ELIGIBILITY . 'checking';

    protected function setUp(): void
    {
        self::bootKernel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGetEligibilitiesNotFoundAction(): void
    {
        /** @var Staff $staff */
        $staff = static::$container->get(StaffRepository::class)->findOneBy(['publicId' => 'staff_company:bar_user-a']);

        $response = $this->createAuthClient($staff)
            ->request(Request::METHOD_GET, self::ENDPOINT_ELIGIBILITY . 'not_an_id')
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function successfullProvider(): iterable
    {
        yield 'user-1 - reservation draft 1 - checking profile without conditions : eligible' => [
            'staff_company:basic_user-1',
            ReservationFixtures::RESERVATION_DRAFT_1,
            'profile',
            false,
            [],
        ];
        yield 'user-1 - reservation draft 2 - checking profile without conditions : ineligible' => [
            'staff_company:basic_user-1',
            ReservationFixtures::RESERVATION_DRAFT_2,
            'profile',
            false,
            [
                'profile' => [
                    'young_farmer',
                    'subsidiary',
                ],
            ],
        ];
        yield 'user-2 - reservation sent 1 - checking profile without conditions : eligible' => [
            'staff_company:basic_user-2',
            ReservationFixtures::RESERVATION_SENT_1,
            'profile',
            false,
            [],
        ];
        yield 'user-2 - reservation sent 1 - checking profile with conditions : eligible' => [
            'staff_company:basic_user-2',
            ReservationFixtures::RESERVATION_SENT_1,
            'profile',
            true,
            [],
        ];
        yield 'user-3 - reservation sent 1 - checking project without conditions : eligible' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_SENT_1,
            'project',
            false,
            [],
        ];
        yield 'user-3 - reservation sent 1 - checking project with conditions : ineligible' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_SENT_1,
            'project',
            true,
            [
                'project' => [
                    'total_fei_credit',
                    'credit_excluding_fei',
                ],
            ],
        ];
        yield 'user-5 - reservation sent 1 - checking loan without conditions : eligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_SENT_1,
            'loan',
            false,
            [],
        ];
        yield 'user-5 - reservation sent 1 - checking loan with conditions : eligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_SENT_1,
            'loan',
            true,
            [],
        ];
        yield 'user-11 - reservation sent 1 - checking conditions : eligible' => [
            'staff_company:basic_user-11',
            ReservationFixtures::RESERVATION_SENT_1,
            null,
            true,
            [],
        ];
        yield 'user-3 - reservation sent 2 - checking profile without conditions : ineligible' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_SENT_2,
            'profile',
            false,
            [
                'profile' => [
                    'young_farmer',
                    'creation_in_progress',
                ],
            ],
        ];
        yield 'user-3 - reservation sent 2 - checking profile with conditions : ineligible' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_SENT_2,
            'profile',
            true,
            [
                'profile' => [
                    'young_farmer',
                    'creation_in_progress',
                ],
            ],
        ];
        yield 'user-4 - reservation sent 2 - checking project without conditions : ineligible' => [
            'staff_company:basic_user-4',
            ReservationFixtures::RESERVATION_SENT_2,
            'project',
            false,
            [
                'project' => [
                    'project_grant',
                ],
            ],
        ];
        yield 'user-4 - reservation sent 2 - checking project with conditions : ineligible' => [
            'staff_company:basic_user-4',
            ReservationFixtures::RESERVATION_SENT_2,
            'project',
            true,
            [
                'project' => [
                    'project_grant',
                ],
            ],
        ];
        yield 'user-5 - reservation sent 2 - checking loan without conditions : eligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_SENT_2,
            'loan',
            false,
            [],
        ];
        yield 'user-5 - reservation sent 2 - checking loan with conditions : ineligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_SENT_2,
            'loan',
            true,
            [
                'loan' => [
                    'loan_duration',
                ],
            ],
        ];
        yield 'user-11 - reservation sent 2 - checking conditions : ineligible' => [
            'staff_company:basic_user-11',
            ReservationFixtures::RESERVATION_SENT_2,
            null,
            true,
            [
                'profile' => [
                    'young_farmer',
                    'creation_in_progress',
                    'turnover',
                    'total_assets',
                ],
                'project' => [
                    'total_fei_credit',
                    'project_grant',
                ],
                'loan' => [
                    'loan_duration',
                ],
            ],
        ];
    }

    /**
     * @dataProvider successfullProvider
     */
    public function testPostEligibilitiesChecking(
        string $staffPublicId,
        string $reservationPublicId,
        ?string $category = null,
        bool $withConditions,
        array $ineligibles
    ): void {
        /** @var IriConverterInterface $iriConverter */
        $iriConverter = static::$container->get(IriConverterInterface::class);

        /** @var Staff $staff */
        $staff = static::$container->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);
        /** @var Reservation $reservation */
        $reservation    = static::$container->get(ReservationRepository::class)->findOneBy(['publicId' => $reservationPublicId]);
        $reservationIri = $iriConverter->getIriFromItem($reservation);

        $response = $this->createAuthClient($staff)
            ->request(Request::METHOD_POST, self::ENDPOINT_ELIGIBILITY_CHECKING, [
                'json' => [
                    'reservation'    => $reservationIri,
                    'category'       => $category,
                    'withConditions' => $withConditions,
                ],
            ])
        ;

        $this->assertResponseIsSuccessful();

        $this->assertJsonContains(['@type' => 'credit_guaranty_eligibility']);
        $this->assertJsonContains(['id' => 'not_an_id']);
        $this->assertJsonContains(['ineligibles' => $ineligibles]);
    }

    public function exceptionProvider(): iterable
    {
        yield 'user-1 - reservation draft 1 - checking profile with conditions' => [
            'staff_company:basic_user-1',
            ReservationFixtures::RESERVATION_DRAFT_1,
            'profile',
            true,
            'Cannot check conditions without Project in reservation #1',
        ];
        yield 'user-2 - reservation draft 1 - checking project without conditions' => [
            'staff_company:basic_user-2',
            ReservationFixtures::RESERVATION_DRAFT_1,
            'project',
            false,
            'Cannot check conditions without Project in reservation #1',
        ];
        yield 'user-2 - reservation draft 1 - checking project with conditions' => [
            'staff_company:basic_user-2',
            ReservationFixtures::RESERVATION_DRAFT_1,
            'project',
            true,
            'Cannot check conditions without Project in reservation #1',
        ];
        yield 'user-3 - reservation draft 1 - checking loan without conditions' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_DRAFT_1,
            'loan',
            false,
            'Cannot check conditions without FinancingObject(s) in reservation #1',
        ];
        yield 'user-3 - reservation draft 1 - checking loan with conditions' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_DRAFT_1,
            'loan',
            true,
            'Cannot check conditions without Project in reservation #1',
        ];
        yield 'user-11 - reservation draft 1 - checking conditions' => [
            'staff_company:basic_user-11',
            ReservationFixtures::RESERVATION_DRAFT_1,
            null,
            true,
            'Cannot check conditions without Project in reservation #1',
        ];
        yield 'user-4 - reservation draft 2 - checking project without conditions' => [
            'staff_company:basic_user-4',
            ReservationFixtures::RESERVATION_DRAFT_2,
            'project',
            false,
            'Cannot check conditions without Project in reservation #2',
        ];
        yield 'user-4 - reservation draft 2 - checking project with conditions' => [
            'staff_company:basic_user-4',
            ReservationFixtures::RESERVATION_DRAFT_2,
            'project',
            true,
            'Cannot check conditions without Project in reservation #2',
        ];
        yield 'user-5 - reservation draft 2 - checking loan without conditions' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_DRAFT_2,
            'loan',
            false,
            'Cannot check conditions without FinancingObject(s) in reservation #2',
        ];
        yield 'user-5 - reservation draft 2 - checking loan with conditions' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_DRAFT_2,
            'loan',
            true,
            'Cannot check conditions without Project in reservation #2',
        ];
        yield 'user-11 - reservation draft 2 - checking conditions' => [
            'staff_company:basic_user-11',
            ReservationFixtures::RESERVATION_DRAFT_2,
            null,
            true,
            'Cannot check conditions without Project in reservation #2',
        ];
    }

    /**
     * @dataProvider exceptionProvider
     */
    public function testPostEligibilitiesCheckingException(
        string $staffPublicId,
        string $reservationPublicId,
        ?string $category = null,
        bool $withConditions,
        string $errorMessage
    ): void {
        /** @var IriConverterInterface $iriConverter */
        $iriConverter = static::$container->get(IriConverterInterface::class);

        /** @var Staff $staff */
        $staff = static::$container->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);
        /** @var Reservation $reservation */
        $reservation    = static::$container->get(ReservationRepository::class)->findOneBy(['publicId' => $reservationPublicId]);
        $reservationIri = $iriConverter->getIriFromItem($reservation);

        $response = $this->createAuthClient($staff)
            ->request(Request::METHOD_POST, self::ENDPOINT_ELIGIBILITY_CHECKING, [
                'json' => [
                    'reservation'    => $reservationIri,
                    'category'       => $category,
                    'withConditions' => $withConditions,
                ],
            ])
        ;

        $this->assertJsonContains(['@type' => 'hydra:Error']);
        $this->assertJsonContains(['hydra:description' => $errorMessage]);
    }

    public function forbiddenProvider(): iterable
    {
        yield 'user-6' => ['staff_company:basic_user-6'];
        yield 'user-7' => ['staff_company:basic_user-7'];
        yield 'user-8' => ['staff_company:basic_user-8'];
        yield 'user-9' => ['staff_company:basic_user-9'];
        yield 'user-10' => ['staff_company:basic_user-10'];
    }

    /**
     * @dataProvider forbiddenProvider
     */
    public function testPostEligibilitiesCheckingForbidden(string $staffPublicId): void
    {
        /** @var IriConverterInterface $iriConverter */
        $iriConverter = static::$container->get(IriConverterInterface::class);

        /** @var Staff $staff */
        $staff = static::$container->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);
        /** @var Reservation $reservation */
        $reservation    = static::$container->get(ReservationRepository::class)->findOneBy(['publicId' => ReservationFixtures::RESERVATION_DRAFT_1]);
        $reservationIri = $iriConverter->getIriFromItem($reservation);

        $response = $this->createAuthClient($staff)
            ->request(Request::METHOD_POST, self::ENDPOINT_ELIGIBILITY_CHECKING, [
                'json' => [
                    'reservation' => $reservationIri,
                    'category'    => 'general',
                ],
            ])
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
