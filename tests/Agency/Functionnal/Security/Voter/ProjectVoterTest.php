<?php

declare(strict_types=1);

namespace Unilend\Test\Agency\Functionnal\Security\Voter;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Security\Voter\ProjectVoter;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;

/**
 * @covers \Unilend\Agency\Security\Voter\ProjectVoter
 *
 * @coversDefaultClass \Unilend\Agency\Security\Voter\ProjectVoter
 *
 * @internal
 */
class ProjectVoterTest extends KernelTestCase
{
    /**
     * @covers ::canView
     * @covers ::vote
     * @covers ::voteOnAttribute
     *
     * @dataProvider providerView
     * @dataProvider providerEdit
     * @dataProvider providerCreate
     * @dataProvider providerAgent
     * @dataProvider providerParticipant
     * @dataProvider providerBorrower
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

        yield from $this->formatProviderData(ProjectVoter::ATTRIBUTE_AGENT, $tests);
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
            'PARTICIPANT: Connected user with unkown bank in project is not participant in draft project' => [
                'staff_company:basic_user:1',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'PARTICIPANT: Connected user with unkown bank in project is not participant in published project' => [
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

        yield from $this->formatProviderData(ProjectVoter::ATTRIBUTE_PARTICIPANT, $tests);
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

        yield from $this->formatProviderData(ProjectVoter::ATTRIBUTE_BORROWER, $tests);
    }

    private function loginUser(User $user): TokenInterface
    {
        // Must use clone because of User::setCurrentStaff
        // Otherwise the successive calls to setCurrentStaff overwrites the last value
        // TODO Remove clone when VoterRefactor (can* calls based on token instead of user) is made
        return new UsernamePasswordToken(clone $user, $user->getPassword(), 'api', $user->getRoles());
    }

    private function loginStaff(Staff $staff): TokenInterface
    {
        $token = $this->loginUser($staff->getUser());

        /** @var User $user */
        $user = $token->getUser();

        $user->setCurrentStaff($staff);
        $token->setAttribute('staff', $staff);
        $token->setAttribute('company', $staff->getCompany());

        return $token;
    }

    private function fetchEntities(EntityManagerInterface $em, string $class, array $publicIds = [])
    {
        return $em->createQueryBuilder()
            ->select('u')
            ->from($class, 'u', 'u.publicId')
            ->where('u.publicId IN (:publicIds)')
            ->setParameter('publicIds', $publicIds)
            ->getQuery()
            ->getResult()
            ;
    }

    private function formatProviderData(string $attribute, array $tests = []): iterable
    {
        static::bootKernel();

        /** @var EntityManagerInterface $em */
        $em = static::$container->get(EntityManagerInterface::class);

        $projects = $this->fetchEntities($em, Project::class, array_column($tests, 1));

        $staffs = $this->fetchEntities($em, Staff::class, array_column($tests, 0));

        $users = $this->fetchEntities($em, User::class, array_column($tests, 0));

        $tokens = array_map([$this, 'loginStaff'], $staffs);

        $tokens = array_merge($tokens, array_map([$this, 'loginUser'], $users));

        foreach ($tests as $test => [$token, $project, $expected]) {
            yield $test          => [$tokens[$token], \is_string($project) ? $projects[$project] : $project, $attribute, $expected];
        }
    }
}
