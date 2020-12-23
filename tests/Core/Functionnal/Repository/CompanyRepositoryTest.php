<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Functionnal\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Unilend\Core\Repository\CompanyRepository;
use Unilend\Core\Repository\TeamRepository;

/**
 * @covers \Unilend\Core\Repository\CompanyRepository
 */
class CompanyRepositoryTest extends KernelTestCase
{

    /**
     * @return void
     */
    protected function setUp(): void
    {
        static::bootKernel();
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function testFindByTeam()
    {
        /** @var TeamRepository $teamRepository */
        $teamRepository = static::$container->get(TeamRepository::class);

        $team = $teamRepository->findOneBy(['publicId' => 'team/A_company/basic']);

        /** @var CompanyRepository $companyRepository */
        $companyRepository = static::$container->get(CompanyRepository::class);

        $result = $companyRepository->findOneByTeam($team);

        static::assertSame(
            $companyRepository->findOneBy(['publicId' => 'company/basic']),
            $result
        );
    }
}
