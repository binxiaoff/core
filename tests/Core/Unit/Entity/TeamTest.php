<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Team;

/**
 * @coversDefaultClass \Unilend\Core\Entity\Team
 *
 * @internal
 */
class TeamTest extends TestCase
{
    /**
     * @covers ::createTeam
     */
    public function testCreateTeam()
    {
        $company = new Company('company', 'company', '850890666');

        $name        = 'test';
        $createdTeam = Team::createTeam($name, $company->getRootTeam());

        static::assertSame($company->getRootTeam(), $createdTeam->getParent());
        static::assertSame($name, $createdTeam->getName());
        static::assertSame($company->getRootTeam(), $createdTeam->getRoot());
        static::assertSame($company->getRootTeam(), $createdTeam->getParent());
        static::assertEmpty($createdTeam->getChildren());
        static::assertEmpty($createdTeam->getDescendents());
        static::assertCount(1, $company->getRootTeam()->getChildren());
    }

    /**
     * @covers ::createRootTeam
     */
    public function testCreateRootTeam()
    {
        $company = new Company('company', 'company', '850890666');

        $rootTeam = Team::createRootTeam($company);

        static::assertSame($company, $rootTeam->getCompany());
        static::assertCount(0, $rootTeam->getChildren());
        static::assertCount(0, $rootTeam->getDescendents());
        static::assertTrue($rootTeam->isRoot());
        static::assertNull($rootTeam->getParent());
        static::assertEmpty($rootTeam->getAncestors());
    }

    /**
     * @covers ::isRoot
     */
    public function testIsRoot()
    {
        $teams = $this->getTestTree();

        static::assertTrue($teams['root']->isRoot());
        static::assertFalse($teams['A']->isRoot());
    }

    /**
     * @covers ::getDescendents
     */
    public function testGetDescendents()
    {
        $team = $this->getTestTree();

        static::assertEmpty($team['C']->getDescendents());
        static::assertEmpty($team['3']->getDescendents());
        static::assertEqualsCanonicalizing([$team['#'], $team['@']], $team['A']->getDescendents());
        static::assertEqualsCanonicalizing(array_map(static fn ($index) => $team[$index], ['A', '#', '@', 'B']), $team['1']->getDescendents());
        static::assertNotContains($team['root'], $team['root']->getDescendents());
    }

    /**
     * @covers ::getAncestors
     */
    public function testGetAncestors()
    {
        $teams = $this->getTestTree();

        static::assertEqualsCanonicalizing([$teams['A'], $teams['1'], $teams['root']], $teams['#']->getAncestors());
        static::assertNotContains($teams['#'], $teams['#']->getAncestors());
        static::assertEqualsCanonicalizing([$teams['1'], $teams['root']], $teams['A']->getAncestors());
        static::assertEqualsCanonicalizing([$teams['1'], $teams['root']], $teams['B']->getAncestors());
        static::assertEqualsCanonicalizing([$teams['root']], $teams['1']->getAncestors());
        static::assertEmpty($teams['root']->getAncestors());
    }

    /**
     * @covers ::getChildren
     */
    public function testGetChildren()
    {
        $team = $this->getTestTree();

        static::assertEmpty($team['C']->getChildren());
        static::assertEmpty($team['3']->getChildren());
        static::assertEqualsCanonicalizing([$team['#'], $team['@']], $team['A']->getChildren());
        static::assertEqualsCanonicalizing(array_map(static fn ($index) => $team[$index], ['A', 'B']), $team['1']->getChildren());
        static::assertNotContains($team['root'], $team['root']->getChildren());
    }

    /**
     * @covers ::getParent
     */
    public function testGetParent()
    {
        $team = $this->getTestTree();

        static::assertSame($team['A'], $team['#']->getParent());
        static::assertSame($team['1'], $team['A']->getParent());
        static::assertSame($team['root'], $team['1']->getParent());
        static::assertNull($team['root']->getParent());
    }

    /**
     * @covers ::getRoot
     */
    public function testGetRoot()
    {
        $teams = $this->getTestTree();

        static::assertSame($teams['root'], $teams['root']->getRoot());
        static::assertSame($teams['root'], $teams['C']->getRoot());
        static::assertSame($teams['root'], $teams['3']->getRoot());
        static::assertSame($teams['root'], $teams['#']->getRoot());
    }

    /**
     * @return array|Team[]
     */
    private function getTestTree(): array
    {
        $tree = [
            '1' => [
                'A' => [
                    '#' => [],
                    '@' => [],
                ],
                'B' => [],
            ],
            '2' => [
                'C' => [],
            ],
            '3' => [],
        ];

        $company = new Company('team', 'team', '850890666');

        // Inner function to create tree (use recursion)
        // passing reference to the function to allow recursion
        $fn = static function ($root, $children) use (&$fn) {
            foreach ($children as $name => $next) {
                $fn(Team::createTeam((string) $name, $root), $next);
            }
        };

        $fn($company->getRootTeam(), $tree);

        $descendents = $company->getRootTeam()->getDescendents();

        $result = array_combine(array_map(static fn (Team $team) => $team->getName(), $descendents), $company->getRootTeam()->getDescendents());

        $result['root'] = $company->getRootTeam();

        return $result;
    }
}
