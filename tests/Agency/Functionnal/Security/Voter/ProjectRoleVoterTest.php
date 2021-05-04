<?php

declare(strict_types=1);

namespace Unilend\Test\Agency\Functionnal\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Security\Voter\ProjectRoleVoter;
use Unilend\Agency\Security\Voter\ProjectVoter;

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

        yield from $this->formatProviderData(ProjectVoter::ROLE_AGENT, $tests);
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
            'PARTICIPANT: Connected user with participant bank is not participant in draft project' => [
                'staff_company:bar_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected manager with participant bank is not participant in draft project' => [
                'staff_company:bar_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected user with participant bank is participant in published project' => [
                'staff_company:bar_user:b',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'PARTICIPANT: Connected manager with participant bank is  participant in published project' => [
                'staff_company:bar_user:a',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
        ];

        yield from $this->formatProviderData(ProjectVoter::ROLE_PARTICIPANT, $tests);
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

        yield from $this->formatProviderData(ProjectVoter::ROLE_BORROWER, $tests);
    }
}
