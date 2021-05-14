<?php

declare(strict_types=1);

namespace Unilend\Test\Agency\Functionnal\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Security\Voter\ProjectRoleVoter;

/**
 * @coversDefaultClass \Unilend\Agency\Security\Voter\ProjectRoleVoter
 *
 * @internal
 */
class ProjectRoleVoterTest extends AbstractProjectVoterTest
{
    /**
     * @covers ::isAgent
     * @covers ::isBorrower
     * @covers ::isParticipant
     * @covers ::vote
     * @covers ::voteOnAttribute
     *
     * @dataProvider providerAgent
     * @dataProvider providerParticipant
     * @dataProvider providerBorrower
     */
    public function testVote(TokenInterface $token, Project $subject, string $attribute, int $expected): void
    {
        static::bootKernel();

        static::$container->get('security.token_storage')->setToken($token);

        $voter = static::$container->get(ProjectRoleVoter::class);

        static::assertSame($expected, $voter->vote($token, $subject, (array) $attribute));
    }

    public function providerAgent(): iterable
    {
        $tests = [];

        foreach (['draft', 'published'] as $project) {
            $tests = array_merge($tests, [
                "AGENT: Connected user without staff cannot is not agent in {$project} project" => [
                    'user:a',
                    $project,
                    VoterInterface::ACCESS_DENIED,
                ],
                "AGENT: Connected user with company not agent is not agent in {$project} project" => [
                    'staff_company:baz_user:a',
                    $project,
                    VoterInterface::ACCESS_DENIED,
                ],
                "AGENT: Connected user with correct company but not in members is not agent in {$project} project" => [
                    'staff_company:foo_user:d',
                    $project,
                    VoterInterface::ACCESS_DENIED,
                ],
                "AGENT: Connected manager with correct company but not subordinate (or self) in  members is not agent in {$project} project" => [
                    'staff_company:foo_user:e',
                    $project,
                    VoterInterface::ACCESS_DENIED,
                ],
                "AGENT: Connected user with agent company is agent in {$project} project" => [
                    'staff_company:foo_user:c',
                    $project,
                    VoterInterface::ACCESS_GRANTED,
                ],
                "AGENT: Connected manager with agent company and subordinate in members is agent in {$project} project" => [
                    'staff_company:foo_user:a',
                    $project,
                    VoterInterface::ACCESS_GRANTED,
                ],
            ]);
        }

        yield from $this->formatProviderData(ProjectRoleVoter::ROLE_AGENT, $tests);
    }

    public function providerParticipant(): iterable
    {
        $tests = [
            'PARTICIPANT: Connected user without staff cannot is not participant in draft project' => [
                'user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected user without staff cannot is not participant in published project' => [
                'user:a',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected user with unknown bank in project is not participant in draft project' => [
                'staff_company:basic_user:1',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected user with unknown bank in project is not participant in published project' => [
                'staff_company:basic_user:1',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected user project creator is not participant in draft project' => [
                'staff_company:foo_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected user agent is not participant in draft project' => [
                'staff_company:foo_user:c',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected manager of project creator is not participant in draft project' => [
                'staff_company:foo_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected user project creator is participant in published project' => [
                'staff_company:foo_user:b',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'PARTICIPANT: Connected manager of project creator is  participant in published project' => [
                'staff_company:foo_user:a',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'PARTICIPANT: Connected user with participant bank is not participant in draft project (primary)' => [
                'staff_company:bar_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected user with participant bank is not participant in draft project (secondary)' => [
                'staff_company:tux_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected manager with participant bank is not participant in draft project (primary)' => [
                'staff_company:bar_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected manager with participant bank is not participant in draft project (secondary)' => [
                'staff_company:tux_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected user with participant bank is participant in published project (primary)' => [
                'staff_company:bar_user:b',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'PARTICIPANT: Connected user with participant bank is participant in published project (secondary)' => [
                'staff_company:tux_user:b',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'PARTICIPANT: Connected manager with participant bank is participant in published project (primary)' => [
                'staff_company:bar_user:a',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'PARTICIPANT: Connected manager with participant bank is participant in published project (secondary)' => [
                'staff_company:bar_user:a',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
        ];

        yield from $this->formatProviderData(ProjectRoleVoter::ROLE_PARTICIPANT, $tests);
    }

    public function providerPrimaryParticipant(): iterable
    {
        $tests = [
            'PARTICIPANT (primary): Connected user without staff cannot is not primary participant in draft project' => [
                'user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (primary): Connected user without staff cannot is not primary participant in published project' => [
                'user:a',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (primary): Connected user with unknown bank in project is not primary participant in draft project' => [
                'staff_company:basic_user:1',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (primary): Connected user with unknown bank in project is not primary participant in published project' => [
                'staff_company:basic_user:1',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (primary): Connected user project creator is not primary participant in draft project' => [
                'staff_company:foo_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (primary): Connected user agent is not primary participant in draft project' => [
                'staff_company:foo_user:c',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (primary): Connected manager of project creator is not primary participant in draft project' => [
                'staff_company:foo_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (primary): Connected user project creator is primary participant in published project' => [
                'staff_company:foo_user:b',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'PARTICIPANT (primary): Connected manager of project creator is primary participant in published project' => [
                'staff_company:foo_user:a',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'PARTICIPANT (primary): Connected user with participant bank in primary pool is not primary participant in draft project' => [
                'staff_company:bar_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (primary): Connected user with participant bank in secondary pool is not primary participant in draft project' => [
                'staff_company:tux_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (primary): Connected manager with participant bank in primary pool is not primary participant in draft project' => [
                'staff_company:bar_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (primary): Connected manager with participant bank in secondary pool is not primary participant in draft project' => [
                'staff_company:tux_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (primary): Connected user with participant bank in primary pool is primary participant in published project' => [
                'staff_company:bar_user:b',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'PARTICIPANT (primary): Connected user with participant bank in secondary pool is not primary participant in published project' => [
                'staff_company:tux_user:b',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (primary): Connected manager with participant bank in primary pool is primary participant in published project' => [
                'staff_company:bar_user:a',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'PARTICIPANT (primary): Connected manager with participant bank in secondary pool is not primary participant in published project' => [
                'staff_company:tux_user:a',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
        ];

        yield from $this->formatProviderData(ProjectRoleVoter::ROLE_PRIMARY_PARTICIPANT, $tests);
    }

    public function providerSecondaryParticipant(): iterable
    {
        $tests = [
            'PARTICIPANT (secondary): Connected user without staff cannot is not secondary participant in draft project' => [
                'user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected user without staff cannot is not secondary participant in published project' => [
                'user:a',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected user with unknown bank in project is not secondary participant in draft project' => [
                'staff_company:basic_user:1',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected user with unknown bank in project is not secondary participant in published project' => [
                'staff_company:basic_user:1',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected user project creator is not secondary participant in draft project' => [
                'staff_company:foo_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected user agent is not secondary participant in draft project' => [
                'staff_company:foo_user:c',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected manager of project creator is not secondary participant in draft project' => [
                'staff_company:foo_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected user project creator is not secondary participant in published project' => [
                'staff_company:foo_user:b',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected manager of project creator is not secondary participant in published project' => [
                'staff_company:foo_user:a',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected user with participant bank in primary pool is not secondary participant in draft project' => [
                'staff_company:bar_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected user with participant bank in secondary pool is not secondary participant in draft project with silent pool enabled' => [
                'staff_company:tux_user:b',
                'draft_silent_pool_enabled',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected user with participant bank in secondary pool is not secondary participant in draft project with silent pool disabled' => [
                'staff_company:tux_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected manager with participant bank in primary pool is not secondary participant in draft project' => [
                'staff_company:bar_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected manager with participant bank in secondary pool is not secondary participant in draft project' => [
                'staff_company:tux_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected user with participant bank in primary pool is not secondary participant in published project' => [
                'staff_company:bar_user:b',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected user with participant bank in secondary pool is secondary participant in published project' => [
                'staff_company:tux_user:b',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'PARTICIPANT (secondary): Connected manager with participant bank in primary pool is not secondary participant in published project' => [
                'staff_company:bar_user:a',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT (secondary): Connected manager with participant bank in secondary pool is secondary participant in published project' => [
                'staff_company:tux_user:a',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
        ];

        yield from $this->formatProviderData(ProjectRoleVoter::ROLE_SECONDARY_PARTICIPANT, $tests);
    }

    public function providerBorrower(): iterable
    {
        $tests = [
            'BORROWER: Connected user unknown in project is not borrower agent in draft project' => [
                'user:£',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'BORROWER: Connected user unknown in project is not borrower agent in published project' => [
                'user:£',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'BORROWER: Connected user with corresponding member in borrower is not borrower in draft project' => [
                'user:+',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'BORROWER: Connected user with corresponding member in borrower is borrower in published project' => [
                'user:+',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'BORROWER: Connected staff without corresponding member in borrower is not borrower in draft project' => [
                'staff_company:bar_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'BORROWER: Connected staff without corresponding member in borrower is not borrower in published project' => [
                'staff_company:bar_user:a',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'BORROWER: Connected staff with corresponding member in borrower is not borrower in draft project' => [
                'staff_company:bar_user:d',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'BORROWER: Connected staff with corresponding member in borrower is borrower in published project' => [
                'staff_company:bar_user:d',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
        ];

        yield from $this->formatProviderData(ProjectRoleVoter::ROLE_BORROWER, $tests);
    }
}
