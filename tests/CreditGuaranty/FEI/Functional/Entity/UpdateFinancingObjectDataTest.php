<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Functional\Entity;

use KLS\Core\Entity\Staff;
use KLS\Core\Repository\StaffRepository;
use KLS\Test\Core\Functional\Api\AbstractApiTest;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversNothing
 *
 * @internal
 */
class UpdateFinancingObjectDataTest extends AbstractApiTest
{
    private const ENDPOINT_UPLOADED_FILE = '/credit_guaranty/financing_objects/import_file/update';

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testUploadFileToUpdateFinancingObject(): void
    {
        /** @var Staff $staff */
        $staff = static::getContainer()->get(StaffRepository::class)
            ->findOneBy(['publicId' => 'staff_company:bar_user-a'])
        ;

        $file = new UploadedFile('tests/CreditGuaranty/FEI/DataFixtures/Files/test.xlsx', 'test.xlsx');

        $this->createAuthClient($staff)->request(Request::METHOD_POST, self::ENDPOINT_UPLOADED_FILE, [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra'   => [
                'files' => [
                    'file' => $file,
                ],
            ],
        ]);

        // We get an error because of the loan number and operation number missing on the database
        self::assertResponseStatusCodeSame(200);
    }
}
