<?php

declare(strict_types=1);

namespace Unilend\Test\Agency\Functionnal\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Security\Voter\ProjectVoter;

/**
 * @coversDefaultClass \Unilend\Agency\Security\Voter\ProjectVoter
 *
 * @internal
 */
class ProjectVoterTest extends AbstractProjectVoterTest
{
    /**
     * @covers ::canCreate
     * @covers ::canEdit
     * @covers ::canView
     * @covers ::vote
     * @covers ::voteOnAttribute
     *
     * @dataProvider providerView
     * @dataProvider providerEdit
     * @dataProvider providerCreate
     */
    public function testVote(TokenInterface $token, Project $subject, string $attribute, int $expected): void
    {
        static::bootKernel();

        static::$container->get('security.token_storage')->setToken($token);

        $voter = static::$container->get(ProjectVoter::class);

        static::assertSame($expected, $voter->vote($token, $subject, (array) $attribute));
    }

    public function providerView(): iterable
    {
        $tests = [
            'VIEW: project creator can view draft project' => [
                'staff_company:foo_user:b',
                'draft',
                VoterInterface::ACCESS_GRANTED,
            ],
            'VIEW: agent project member can view draft project' => [
                'staff_company:foo_user:c',
                'draft',
                VoterInterface::ACCESS_GRANTED,
            ],
            'VIEW: project creator can view published project' => [
                'staff_company:foo_user:b',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'VIEW: agent project member can view published project' => [
                'staff_company:foo_user:c',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'VIEW: manager agent can view draft project' => [
                'staff_company:foo_user:a',
                'draft',
                VoterInterface::ACCESS_GRANTED,
            ],
            'VIEW: manager agent can view published project' => [
                'staff_company:foo_user:a',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'VIEW: participant cannot view draft project' => [
                'staff_company:bar_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW: participant can view published project' => [
                'staff_company:bar_user:b',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'VIEW: manager participant cannot view draft project' => [
                'staff_company:bar_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW: manager participant can view published project' => [
                'staff_company:bar_user:a',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'VIEW: borrower cannot view draft project' => [
                'user:+',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW: borrower can view published project' => [
                'user:+',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],

            'VIEW: connected user with unknown company in project cannot can view draft project' => [
                'staff_company:baz_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW: connected user with unknown company cannot can view published project' => [
                'staff_company:baz_user:b',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],

            'VIEW: connected manager with unknown company in project cannot can view draft project' => [
                'staff_company:baz_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW: connected manager with unknown company cannot can view published project' => [
                'staff_company:baz_user:a',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW: unknown user in project cannot can view draft project' => [
                'user:£',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW: unknown user in project cannot can view published project' => [
                'user:£',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
        ];

        yield from $this->formatProviderData(ProjectVoter::ATTRIBUTE_VIEW, $tests);
    }

    public function providerEdit(): iterable
    {
        $tests = [
            'EDIT: project creator can edit draft project' => [
                'staff_company:foo_user:b',
                'draft',
                VoterInterface::ACCESS_GRANTED,
            ],
            'EDIT: agent project member can edit draft project' => [
                'staff_company:foo_user:c',
                'draft',
                VoterInterface::ACCESS_GRANTED,
            ],
            'EDIT: project creator can edit published project' => [
                'staff_company:foo_user:b',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'EDIT: agent project member can edit published project' => [
                'staff_company:foo_user:c',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],
            'EDIT: manager agent can edit draft project' => [
                'staff_company:foo_user:a',
                'draft',
                VoterInterface::ACCESS_GRANTED,
            ],
            'EDIT: manager agent can edit published project' => [
                'staff_company:foo_user:a',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],

            'EDIT: participant cannot edit draft project' => [
                'staff_company:bar_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'EDIT: participant can edit published project' => [
                'staff_company:bar_user:b',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'EDIT: manager participant cannot edit draft project' => [
                'staff_company:bar_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'EDIT: manager participant can edit published project' => [
                'staff_company:bar_user:a',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'EDIT: borrower cannot edit draft project' => [
                'user:+',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'EDIT: borrower can edit published project' => [
                'user:+',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],

            'EDIT: connected user with unknown company in project cannot can edit draft project' => [
                'staff_company:baz_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'EDIT: connected user with unknown company cannot can edit published project' => [
                'staff_company:baz_user:b',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],

            'EDIT: connected manager with unknown company in project cannot can edit draft project' => [
                'staff_company:baz_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'EDIT: connected manager with unknown company cannot can edit published project' => [
                'staff_company:baz_user:a',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],

            'EDIT: connected user with archived company cannot can edit published project' => [
                'staff_company:qux_user:b',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],

            'EDIT: connected manager with archived company cannot can edit published project' => [
                'staff_company:qux_user:a',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'EDIT: unknown user in project cannot can edit draft project' => [
                'user:£',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'EDIT: unknown user in project cannot can edit published project' => [
                'user:£',
                'published',
                VoterInterface::ACCESS_DENIED,
            ], ];

        yield from $this->formatProviderData(ProjectVoter::ATTRIBUTE_EDIT, $tests);
    }

    public function providerCreate(): iterable
    {
        $tests = [
            'CREATE: Connected user without staff cannot create project' => [
                'user:+',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'CREATE: Connected user with company cannot create a project with another company' => [
                'staff_company:baz_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'CREATE: Connected user with company cannot create a project without correct permission' => [
                'staff_company:foo_user:c',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'CREATE: Connected user with company can create a project with connected company and correct permission' => [
                'staff_company:foo_user:b',
                'draft',
                VoterInterface::ACCESS_GRANTED,
            ],
        ];

        yield from $this->formatProviderData(ProjectVoter::ATTRIBUTE_CREATE, $tests);
    }
}
