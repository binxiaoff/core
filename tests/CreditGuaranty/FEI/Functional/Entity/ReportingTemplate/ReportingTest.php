<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Functional\Entity\ReportingTemplate;

use KLS\Core\Entity\Staff;
use KLS\Core\Repository\StaffRepository;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\Test\Core\Functional\Api\AbstractApiTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @coversNothing
 *
 * @internal
 */
class ReportingTest extends AbstractApiTest
{
    private const ENDPOINT = '/credit_guaranty/reporting_templates/{publicId}/reporting';

    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @dataProvider reportingDataProvider
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testReporting(
        string $staffPublicId,
        string $reportingTemplatePublicId,
        array $queryParams,
        array $expectedResultKeys,
        int $expectedCount
    ): void {
        /** @var Staff $staff */
        $staff = static::getContainer()->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);
        $iri   = \str_replace('{publicId}', $reportingTemplatePublicId, self::ENDPOINT);

        if (false === empty($queryParams)) {
            $iri .= '?' . \implode('&', $queryParams);
        }

        $response = $this->createAuthClient($staff)->request(Request::METHOD_GET, $iri);
        $result   = \json_decode($response->getContent(), true);

        static::assertResponseIsSuccessful();
        static::assertCount($expectedCount, $result);

        foreach ($result as $item) {
            static::assertSame(\array_combine($expectedResultKeys, $item), $item);
        }
    }

    public function reportingDataProvider(): iterable
    {
        yield 'reporting-template-1 - staff_company:bar_user-a' => [
            'staff_company:bar_user-a',
            'reporting-template-1',
            [],
            [
                'id_financing_object',
                FieldAlias::REPORTING_FIRST_DATE,
                FieldAlias::REPORTING_LAST_DATE,
                FieldAlias::REPORTING_VALIDATION_DATE,
                FieldAlias::CREATION_IN_PROGRESS,
                FieldAlias::BENEFICIARY_NAME,
                FieldAlias::COMPANY_NAME,
                FieldAlias::ACTIVITY_DEPARTMENT,
                FieldAlias::RECEIVING_GRANT,
                FieldAlias::AID_INTENSITY,
                FieldAlias::PROJECT_GRANT,
                FieldAlias::SUPPORTING_GENERATIONS_RENEWAL,
                FieldAlias::FINANCING_OBJECT_NAME,
                FieldAlias::LOAN_DURATION,
                FieldAlias::INVESTMENT_LOCATION,
                FieldAlias::RESERVATION_NAME,
                FieldAlias::BORROWER_TYPE_GRADE,
                FieldAlias::LOAN_NEW_MATURITY,
            ],
            9,
        ];
        yield 'reporting-template-1 - staff_company:bar_user-b' => [
            'staff_company:bar_user-b',
            'reporting-template-1',
            [
                FieldAlias::MAPPING_REPORTING_DATES[FieldAlias::REPORTING_FIRST_DATE] . '=null',
                'search=Paris',
            ],
            [
                'id_financing_object',
                FieldAlias::REPORTING_FIRST_DATE,
                FieldAlias::REPORTING_LAST_DATE,
                FieldAlias::REPORTING_VALIDATION_DATE,
                FieldAlias::CREATION_IN_PROGRESS,
                FieldAlias::BENEFICIARY_NAME,
                FieldAlias::COMPANY_NAME,
                FieldAlias::ACTIVITY_DEPARTMENT,
                FieldAlias::RECEIVING_GRANT,
                FieldAlias::AID_INTENSITY,
                FieldAlias::PROJECT_GRANT,
                FieldAlias::SUPPORTING_GENERATIONS_RENEWAL,
                FieldAlias::FINANCING_OBJECT_NAME,
                FieldAlias::LOAN_DURATION,
                FieldAlias::INVESTMENT_LOCATION,
                FieldAlias::RESERVATION_NAME,
                FieldAlias::BORROWER_TYPE_GRADE,
                FieldAlias::LOAN_NEW_MATURITY,
            ],
            5,
        ];
        yield 'reporting-template-1 - staff_company:bar_user-c' => [
            'staff_company:bar_user-c',
            'reporting-template-1',
            [
                FieldAlias::RESERVATION_SIGNING_DATE . '[lte]=1',
                FieldAlias::RESERVATION_EXCLUSION_DATE . '[after]=2021-01-01',
                'test=value',
            ],
            [],
            0,
        ];
        // TODO uncomment this code below in CALS-5310
        // this actually returns data with the 4 properties by default
        // [id_financing_object, reporting_first_date, reporting_last_date, reporting_validation_date]
        // instead of empty array while this reportingTemplate has no reportingTemplateField
//        yield 'reporting-template-2 - staff_company:bar_user-a' => [
//            'staff_company:bar_user-a',
//            'reporting-template-2',
//            [],
//            [],
//            0,
//        ];
    }
}
