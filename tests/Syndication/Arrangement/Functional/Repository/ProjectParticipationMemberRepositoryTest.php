<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\Functional\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use KLS\Core\Repository\StaffRepository;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;
use KLS\Syndication\Arrangement\Repository\ProjectParticipationMemberRepository;
use KLS\Syndication\Arrangement\Repository\ProjectParticipationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @coversDefaultClass \KLS\Syndication\Arrangement\Repository\ProjectParticipationMemberRepository
 *
 * @internal
 */
class ProjectParticipationMemberRepositoryTest extends KernelTestCase
{
    private array $projectParticipationMembers;

    protected function setUp(): void
    {
        static::bootKernel();

        /** @var ProjectParticipationMemberRepository $projectParticipationMemberRepository */
        $projectParticipationMemberRepository = static::$container->get(ProjectParticipationMemberRepository::class);

        $result = $projectParticipationMemberRepository->findAll();

        $this->projectParticipationMembers = [];

        foreach ($result as $projectParticipationMember) {
            $this->projectParticipationMembers[$this->getKey($projectParticipationMember)] = $projectParticipationMember;
        }
    }

    /**
     * @return array[]
     */
    public function providerFindByManager(): array
    {
        return [
            'It should return managed staff who part of any project' => [
                'publicId' => 'staff_company:example_user-15',
                [
                    'example_arranger_example_user-20',
                    'example_arranger_example_user-9',
                    'example_arranger_example_user-10',
                    'basic_arranger_example_user-9',
                ],
            ],
            'It should yield no result for non manager staff' => [
                'staff_company:basic_user-7',
                [],
            ],
            'It should return at least the queried manager staff' => [
                'staff_company:basic_user-11',
                [
                    'basic_arranger_basic_user-11',
                ],
            ],
        ];
    }

    /**
     * @covers ::findActiveByManager
     *
     * @dataProvider providerFindByManager
     *
     * @param mixed $expected
     */
    public function testFindActiveByManager(string $managerPublicId, $expected): void
    {
        /** @var StaffRepository $staffRepository */
        $staffRepository = static::$container->get(StaffRepository::class);

        $manager = $staffRepository->findOneBy(['publicId' => $managerPublicId]);

        $expected = \array_map(function ($key) {
            return $this->projectParticipationMembers[$key]->getPublicId();
        }, $expected);

        /** @var ProjectParticipationMemberRepository $projectParticipationMemberRepository */
        $projectParticipationMemberRepository = static::$container->get(ProjectParticipationMemberRepository::class);

        $result = $projectParticipationMemberRepository->findActiveByManager($manager);

        static::assertIsArray($result);
        static::assertCount(\count($expected), $result);
        static::assertContainsOnlyInstancesOf(ProjectParticipationMember::class, $result);

        foreach ($result as $item) {
            static::assertContains($item->getPublicId(), $expected);
        }
    }

    /**
     * @dataProvider providerFindByProjectParticipationAndManagerAndPermissionEnabled
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function testFindByProjectParticipationAndManagerAndPermissionEnabled(
        array $projectParticipationCriteria,
        array $managerCriteria,
        int $permission,
        array $expected
    ): void {
        /** @var ProjectParticipationRepository $projectParticipationRepository */
        $projectParticipationRepository = static::$container->get(ProjectParticipationRepository::class);

        /** @var ProjectParticipation $projectParticipation */
        $projectParticipation = $projectParticipationRepository->createQueryBuilder('pp')
            ->innerJoin('pp.participant', 'participant')
            ->innerJoin('pp.project', 'project')
            ->where('participant.publicId = :participant')
            ->andWhere('project.publicId = :project')
            ->setParameters($projectParticipationCriteria)
            ->getQuery()
            ->getSingleResult()
        ;

        /** @var StaffRepository $staffRepository */
        $staffRepository = static::$container->get(StaffRepository::class);

        $manager = $staffRepository->findOneBy($managerCriteria);
        /** @var ProjectParticipationMemberRepository $projectParticipationMemberRepository */
        $projectParticipationMemberRepository = static::$container->get(ProjectParticipationMemberRepository::class);

        $expected = \array_map(function ($key) {
            return $this->projectParticipationMembers[$key]->getPublicId();
        }, $expected);

        $result = $projectParticipationMemberRepository->findActiveByProjectParticipationAndManagerAndPermissionEnabled($projectParticipation, $manager, $permission);

        static::assertIsArray($result);
        static::assertCount(\count($expected), $result);
        static::assertContainsOnlyInstancesOf(ProjectParticipationMember::class, $result);

        /** @var ProjectParticipationMember $item */
        foreach ($result as $item) {
            static::assertContains($item->getPublicId(), $expected);
            static::assertTrue($item->getPermissions()->has($permission));
            static::assertSame($item->getProjectParticipation()->getPublicId(), $projectParticipation->getPublicId());
        }
    }

    /**
     * @return array[]
     */
    public function providerFindByProjectParticipationAndManagerAndPermissionEnabled(): array
    {
        return [
            'It should return no result for non manager staff' => [
                ['participant' => 'company:basic', 'project' => 'project/basic_arranger'],
                ['publicId' => 'staff_company:basic_user-7'],
                0,
                [],
            ],
            'It should return no result for manager staff on incorrect participation' => [
                ['participant' => 'company:example', 'project' => 'project/basic_arranger'],
                ['publicId' => 'staff_company:basic_user-1'],
                0,
                [],
            ],
            'It should return participation members managed by given staff' => [
                ['participant' => 'company:basic', 'project' => 'project/example_arranger'],
                ['publicId' => 'staff_company:basic_user-1'],
                0,
                [
                    'example_arranger_basic_user-4',
                    'example_arranger_basic_user-10',
                    'example_arranger_basic_user-3',
                    'example_arranger_basic_user-8',
                ],
            ],
            'It should return participation members managed by given staff 2' => [
                ['participant' => 'company:basic', 'project' => 'project/example_arranger'],
                ['publicId' => 'staff_company:basic_user-4'],
                0,
                [
                    'example_arranger_basic_user-4',
                    'example_arranger_basic_user-10',
                ],
            ],
            'It should return participation members with specified permission managed by given staff ' => [
                ['participant' => 'company:basic', 'project' => 'project/example_arranger'],
                ['publicId' => 'staff_company:basic_user-1'],
                1,
                [
                    'example_arranger_basic_user-3',
                    'example_arranger_basic_user-8',
                ],
            ],
        ];
    }

    private function getKey(ProjectParticipationMember $projectParticipationMember): string
    {
        $projectParticipation = $projectParticipationMember->getProjectParticipation();
        $project              = $projectParticipation->getProject();
        $company              = $projectParticipation->getParticipant();
        $user                 = $projectParticipationMember->getStaff()->getUser();

        return $project->getTitle() . '_' . $company->getDisplayName() . '_' . $user->getPublicId();
    }
}
