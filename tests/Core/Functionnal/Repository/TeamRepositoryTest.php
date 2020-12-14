<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Functional\Repository;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Unilend\Core\Entity\Team;
use Unilend\Core\Repository\TeamRepository;

/**
 * @coversDefaultClass TeamRepository
 */
class TeamRepositoryTest extends KernelTestCase
{
    /**
     * @var array|Team[]
     */
    private array $teams;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        static::bootKernel();

        /** @var TeamRepository $repository */
        $repository = static::$container->get(TeamRepository::class);

        $this->teams = $repository->createQueryBuilder('c', 'c.name')
            ->where("c.publicId LIKE '%basic%'")
            ->getQuery()
            ->getResult();
    }

    /**
     * @covers ::findRootPath
     *
     * @dataProvider providerFindRootPath
     *
     * @param string $query
     * @param array  $expected
     */
    public function testFindRootPath(string $query, array $expected)
    {
        $result = $this->baseTestTreeQueries('findRootPath', $query, $expected);

        $parents = [];

        /** @var Team $team */
        foreach ($result as $team) {
            $parents[] = $team->getParent();
        }

        static::assertContains(null, $parents, 'as root is return there must be at least one team with ne parents');
        //static::assertSame(array_unique($parents), $parents, 'each parent should be unique');
    }

    /**
     * @return array[]
     */
    public function providerFindRootPath(): array
    {
        return [
            'returns at least root when root is queried' => ['root', ['root']],
            'one level' => ['A', ['root', 'A']],
            'common case' => ['d', ['root', 'A', '1', 'd']],
        ];
    }
    /**
     * @covers ::findSubtree
     *
     * @dataProvider providerFindSubtree
     *
     * @param string $query
     * @param array  $expected
     */
    public function testFindSubtree(string $query, array $expected)
    {
        $this->baseTestTreeQueries('findSubtree', $query, $expected);
    }

    /**
     * @return array[]
     */
    public function providerFindSubtree(): array
    {
        return [
            'returns at least queried leaf' => ['d', ['d']],
            'returns whole tree when queried from root' => ['root', ['root', 'A', 'B', 1, 2, 4, 3, 'd', 'c', '.', '%', '£']],
            'returns subtree' => ['1', ['1', 'd', 'c', '.', '%', '£']],
        ];
    }

    /**
     * @param string $method
     * @param string $query
     * @param array  $expected
     *
     * @return iterable|Team[]
     */
    public function baseTestTreeQueries(string $method, string $query, array $expected)
    {
        $expected = array_intersect_key($this->teams, array_flip($expected));

        /** @var TeamRepository $teamRepository */
        $teamRepository = static::$container->get(TeamRepository::class);

        $result = $teamRepository->{$method}($this->teams[$query]);

        static::assertCount(count($expected), $result);
        static::assertContainsOnlyInstancesOf(Team::class, $result);
        static::assertIsIterable($result);

        foreach ($result as $item) {
            static::assertContains($item, $expected);
        }

        return $result;
    }

    /**
     * @covers ::isRootPathNode
     *
     * @dataProvider providerIsRootPathNode
     *
     * @param string $query
     * @param string $leaf
     * @param bool   $expected
     */
    public function testIsRootPathNode(string $query, string $leaf, bool $expected)
    {
        $query = $this->teams[$query];
        $leaf = $this->teams[$leaf];

        /** @var TeamRepository $teamRepository */
        $teamRepository = static::$container->get(TeamRepository::class);

        $result = $teamRepository->isRootPathNode($query, $leaf);

        static::assertSame($expected, $result);
    }

    /**
     * @return array[]
     */
    public function providerIsRootPathNode(): array
    {
        return [
            'Root team is root path node of self' => [
                'root',
                'root',
                true,
            ],
            'Node is root path node of self' => [
                '1',
                '1',
                true,
            ],
            'Team on another subtree is not root path node' => [
                '1',
                '3',
                false,
            ],
            'Parent is root path node of query' => [
                'A',
                '1',
                true,
            ],
            'Child team is not root path node' => [
                '1',
                'A',
                false,
            ],
        ];
    }
}
