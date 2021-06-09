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
        yield 'authorized staff #staff_company:basic_user-1' => [
            'staff_company:basic_user-1',
            ReservationFixtures::RESERVATION_DRAFT,
            'general',
            true,
        ];
        yield 'authorized staff #staff_company:basic_user-2' => [
            'staff_company:basic_user-2',
            ReservationFixtures::RESERVATION_DRAFT,
            'profile',
            true,
        ];
        yield 'authorized staff #staff_company:basic_user-3' => [
            'staff_company:basic_user-3',
            ReservationFixtures::RESERVATION_SENT,
            'generale',
            true,
        ];
        yield 'authorized staff #staff_company:basic_user-4' => [
            'staff_company:basic_user-4',
            ReservationFixtures::RESERVATION_SENT,
            'profile',
            true,
        ];
        yield 'authorized staff #staff_company:basic_user-5' => [
            'staff_company:basic_user-5',
            ReservationFixtures::RESERVATION_SENT,
            'activity',
            true,
        ];
        yield 'authorized staff #staff_company:basic_user-11' => [
            'staff_company:basic_user-11',
            ReservationFixtures::RESERVATION_SENT,
            'project',
            true,
        ];
    }

    /**
     * @dataProvider authorizedStaffProvider
     */
    public function testPostEligibilitiesCheckingTrue(
        string $staffPublicId,
        string $reservationPublicId,
        string $category,
        bool $eligible
    ): void {
        static::ensureKernelShutdown();
        $client = static::createClient();

        /** @var IriConverterInterface $iriConverter */
        $iriConverter = static::$container->get(IriConverterInterface::class);

        /** @var Staff $staff */
        $staff = static::$container->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);
        /** @var Reservation $reservation */
        $reservation    = static::$container->get(ReservationRepository::class)->findOneBy(['publicId' => $reservationPublicId]);
        $reservationIri = $iriConverter->getIriFromItem($reservation);

        $response = $this->createAuthClient($staff, $client)
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
        yield 'unauthorized staff #staff_company:basic_user-6' => ['staff_company:basic_user-6'];
        yield 'unauthorized staff #staff_company:basic_user-7' => ['staff_company:basic_user-7'];
        yield 'unauthorized staff #staff_company:basic_user-8' => ['staff_company:basic_user-8'];
        yield 'unauthorized staff #staff_company:basic_user-9' => ['staff_company:basic_user-9'];
        yield 'unauthorized staff #staff_company:basic_user-10' => ['staff_company:basic_user-10'];
    }

    /**
     * @dataProvider unauthorizedStaffProvider
     */
    public function testPostEligibilitiesCheckingFalse(string $staffPublicId): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        /** @var IriConverterInterface $iriConverter */
        $iriConverter = static::$container->get(IriConverterInterface::class);

        /** @var Staff $staff */
        $staff = static::$container->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);
        /** @var Reservation $reservation */
        $reservation    = static::$container->get(ReservationRepository::class)->findOneBy(['publicId' => ReservationFixtures::RESERVATION_DRAFT]);
        $reservationIri = $iriConverter->getIriFromItem($reservation);

        $response = $this->createAuthClient($staff, $client)
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
