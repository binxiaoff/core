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

    public function authorizedStaffProvider(): iterable
    {
        yield 'staff_company:basic_user-1 - reservation draft 1 - profile - eligible' => [
            'staff_company:basic_user-1',
            ReservationFixtures::RESERVATION_DRAFT_1,
            'profile',
            true,
        ];
        yield 'staff_company:basic_user-1 - reservation draft 2 - profile - ineligible' => [
            'staff_company:basic_user-1',
            ReservationFixtures::RESERVATION_DRAFT_2,
            'profile',
            false,
        ];
        yield 'staff_company:basic_user-2 - reservation sent 1 - profile - eligible' => [
            'staff_company:basic_user-2',
            ReservationFixtures::RESERVATION_SENT_1,
            'profile',
            true,
        ];
        yield 'staff_company:basic_user-3 - reservation sent 2 - profile - ineligible' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_SENT_2,
            'profile',
            false,
        ];
        yield 'staff_company:basic_user-4 - reservation sent 1 - project - eligible' => [
            'staff_company:basic_user-4',
            ReservationFixtures::RESERVATION_SENT_1,
            'project',
            true,
        ];
        yield 'staff_company:basic_user-4 - reservation sent 2 - project - eligible' => [
            'staff_company:basic_user-4',
            ReservationFixtures::RESERVATION_SENT_2,
            'project',
            true,
        ];
        yield 'staff_company:basic_user-5 - reservation sent 1 - loan - eligible' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_SENT_1,
            'loan',
            true,
        ];
        yield 'staff_company:basic_user-11 - reservation sent 2 - loan - ineligible' => [
            'staff_company:basic_user-11',
            ReservationFixtures::RESERVATION_SENT_2,
            'loan',
            false,
        ];
    }

    /**
     * @dataProvider authorizedStaffProvider
     */
    public function testPostEligibilitiesChecking(
        string $staffPublicId,
        string $reservationPublicId,
        string $category,
        bool $eligible
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
                    'reservation' => $reservationIri,
                    'category'    => $category,
                ],
            ])
        ;

        $this->assertResponseIsSuccessful();

        $this->assertJsonContains(['@type' => 'credit_guaranty_eligibility']);
        $this->assertJsonContains(['id' => 'not_an_id']);
        $this->assertJsonContains(['eligible' => $eligible]);
    }

    public function unauthorizedStaffProvider(): iterable
    {
        yield 'staff_company:basic_user-6' => ['staff_company:basic_user-6'];
        yield 'staff_company:basic_user-7' => ['staff_company:basic_user-7'];
        yield 'staff_company:basic_user-8' => ['staff_company:basic_user-8'];
        yield 'staff_company:basic_user-9' => ['staff_company:basic_user-9'];
        yield 'staff_company:basic_user-10' => ['staff_company:basic_user-10'];
    }

    /**
     * @dataProvider unauthorizedStaffProvider
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
