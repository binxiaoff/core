<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Functional\Entity;

use KLS\Core\Entity\Staff;
use KLS\Core\Repository\StaffRepository;
use KLS\Test\Core\Functional\Api\AbstractApiTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversNothing
 *
 * @internal
 */
class ReportingTemplateDownloadTest extends AbstractApiTest
{
    private const ENDPOINT = '/credit_guaranty/reporting_templates/{publicId}/import-file/download';

    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @dataProvider successfullProvider
     */
    public function testImportFileDownload(string $staffPublicId): void
    {
        /** @var Staff $staff */
        $staff = static::getContainer()->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);

        $response = $this->createAuthClient($staff)->request(Request::METHOD_GET, \str_replace('{publicId}', 'reporting-template-1', self::ENDPOINT));

        $this->assertResponseIsSuccessful();

        $headers = $response->getHeaders();
        static::assertArrayHasKey('content-disposition', $headers);
        static::assertArrayHasKey('content-type', $headers);
        static::assertSame('attachment; filename=kls_credit-and-guaranty_import-file_1.0.xlsx', $headers['content-disposition'][0]);
        static::assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $headers['content-type'][0]);
    }

    public function successfullProvider(): iterable
    {
        yield 'staff_company:bar_user-a' => ['staff_company:bar_user-a'];
        yield 'staff_company:bar_user-b' => ['staff_company:bar_user-b'];
        yield 'staff_company:bar_user-c' => ['staff_company:bar_user-c'];
        yield 'staff_company:bar_user-d' => ['staff_company:bar_user-d'];
        yield 'staff_company:bar_user-e' => ['staff_company:bar_user-e'];
    }

    /**
     * @dataProvider forbiddenProvider
     */
    public function testImportFileDownloadForbidden(string $staffPublicId): void
    {
        /** @var Staff $staff */
        $staff = static::getContainer()->get(StaffRepository::class)->findOneBy(['publicId' => $staffPublicId]);

        $response = $this->createAuthClient($staff)->request(Request::METHOD_GET, \str_replace('{publicId}', 'reporting-template-1', self::ENDPOINT));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function forbiddenProvider(): iterable
    {
        yield 'staff_company:foo_user-a' => ['staff_company:foo_user-a'];
        yield 'staff_company:foo_user-b' => ['staff_company:foo_user-b'];
        yield 'staff_company:foo_user-c' => ['staff_company:foo_user-c'];
        yield 'staff_company:foo_user-d' => ['staff_company:foo_user-d'];
        yield 'staff_company:foo_user-e' => ['staff_company:foo_user-e'];
    }
}
