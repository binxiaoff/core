<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\Functional\Entity;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use KLS\Core\Repository\StaffRepository;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;
use KLS\Syndication\Arrangement\Repository\ProjectParticipationMemberRepository;
use KLS\Syndication\Arrangement\Repository\ProjectParticipationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @coversDefaultClass \KLS\Syndication\Arrangement\Entity\ProjectParticipation
 *
 * @internal
 */
class ProjectParticipationTest extends KernelTestCase
{
    /**
     * @dataProvider providerFindActiveMember
     *
     * @covers ::getManagedMembersOfPermission
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function testGetManagedMembersOfPermission(
        array $projectParticipationCriteria,
        array $managerCriteria,
        int $permission,
        array $expected
    ): void {
        $container = static::getContainer();
        /** @var ProjectParticipationMemberRepository $projectParticipationMemberRepository */
        $projectParticipationMemberRepository = static::getContainer()
            ->get(ProjectParticipationMemberRepository::class)
        ;

        $result = $projectParticipationMemberRepository->findAll();

        $allProjectParticipationMembers = [];

        foreach ($result as $projectParticipationMember) {
            $allProjectParticipationMembers[$this->getKey($projectParticipationMember)] = $projectParticipationMember;
        }

        /** @var ProjectParticipationRepository $projectParticipationRepository */
        $projectParticipationRepository = $container->get(ProjectParticipationRepository::class);

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
        $staffRepository = $container->get(StaffRepository::class);

        $manager = $staffRepository->findOneBy($managerCriteria);

        $expected = \array_map(static function ($key) use ($allProjectParticipationMembers) {
            return $allProjectParticipationMembers[$key]->getPublicId();
        }, $expected);

        $result = $projectParticipation->getManagedMembersOfPermission($manager, $permission);

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
    public function providerFindActiveMember(): array
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
