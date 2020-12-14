<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Functional\Repository;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Repository\CompanyRepository;
use Unilend\Core\Repository\StaffRepository;

/**
 * @coversDefaultClass StaffRepository
 */
class StaffRepositoryTest extends KernelTestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        static::bootKernel();
    }

    /**
     * @covers ::isSuperior
     *
     * @dataProvider providerIsSuperior
     *
     * @param Staff $superior
     * @param Staff $staff
     * @param bool  $expected
     */
    public function testIsSuperior(Staff $superior, Staff $staff, bool $expected)
    {
        /** @var StaffRepository $staffRepository */
        $staffRepository = static::$container->get(StaffRepository::class);

        $result = $staffRepository->isSuperior($superior, $staff);

        static::assertSame($expected, $result);
    }

    /**
     * @return array[]
     */
    public function providerIsSuperior()
    {
        static::bootKernel();

        /** @var StaffRepository $repository */
        $repository = static::$container->get(StaffRepository::class);

        $fixtures = $repository->createQueryBuilder('s', 's.publicId')->getQuery()->getResult();

        return [
            'manager on same team of staff is superior' => [
                $fixtures['staff_company/basic_user/5'],
                $fixtures['staff_company/basic_user/6'],
                true,
            ],
            'manager is superior of self' => [
                $fixtures['staff_company/basic_user/5'],
                $fixtures['staff_company/basic_user/5'],
                true,
            ],
            'manager of parent team is superior of staff of child team' => [
                $fixtures['staff_company/basic_user/1'],
                $fixtures['staff_company/basic_user/6'],
                true,
            ],
            'manager of parent team is superior of manager of child team' => [
                $fixtures['staff_company/basic_user/1'],
                $fixtures['staff_company/basic_user/6'],
                true,
            ],
            'manager are not superiors of staff of different tree' => [
                $fixtures['staff_company/basic_user/11'],
                $fixtures['staff_company/basic_user/9'],
                false,
            ],
            'staff is not superior of another staff' => [
                $fixtures['staff_company/basic_user/6'],
                $fixtures['staff_company/basic_user/3'],
                false,
            ],
            'staff is not superior of self' => [
                $fixtures['staff_company/basic_user/6'],
                $fixtures['staff_company/basic_user/6'],
                false,
            ],
        ];
    }

    /**
     * @covers ::findByCompany
     */
    public function testFindByCompany()
    {
        /** @var CompanyRepository $companyRepository */
        $companyRepository = static::$container->get(CompanyRepository::class);

        $company = $companyRepository->findOneBy(['publicId' => 'company/basic']);

        /** @var StaffRepository $staffRepository */
        $staffRepository = static::$container->get(StaffRepository::class);

        $result = $staffRepository->findByCompany($company);

        static::assertIsIterable($result);
        static::assertTrue(is_countable($result));
        static::assertCount(12, $result);
        static::assertContainsOnlyInstancesOf(Staff::class, $result);

        $expectedPublicIds = array_map(static fn (int $i) => 'staff_company/basic_user/' . $i, range(1, 12));

        $resultPublicIds = [];

        foreach ($result as $staff) {
            $resultPublicIds[] = $staff->getPublicId();
        }

        // Sort is needed because checks order whereas we don't need to test it
        self::assertSame(sort($expectedPublicIds), sort($resultPublicIds));
    }
}
