<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\Functional\Repository;

use KLS\Core\Repository\StaffRepository;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;
use KLS\Syndication\Arrangement\Repository\ProjectParticipationMemberRepository;
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
        $projectParticipationMemberRepository = static::getContainer()
            ->get(ProjectParticipationMemberRepository::class)
        ;

        $result = $projectParticipationMemberRepository->findAll();

        $this->projectParticipationMembers = [];

        foreach ($result as $projectParticipationMember) {
            $key                                     = $this->getKey($projectParticipationMember);
            $this->projectParticipationMembers[$key] = $projectParticipationMember;
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
                    'finished_example_user-9',
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

    private function getKey(ProjectParticipationMember $projectParticipationMember): string
    {
        $projectParticipation = $projectParticipationMember->getProjectParticipation();
        $project              = $projectParticipation->getProject();
        $company              = $projectParticipation->getParticipant();
        $user                 = $projectParticipationMember->getStaff()->getUser();

        return $project->getTitle() . '_' . $company->getDisplayName() . '_' . $user->getPublicId();
    }
}
