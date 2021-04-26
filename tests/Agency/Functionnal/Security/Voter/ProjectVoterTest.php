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
 * @coversDefaultClass \Unilend\Syndication\Security\Voter\ProjectVoter
 *
 * @internal
 */
class ProjectVoterTest extends KernelTestCase
{
    /**
     * @covers ::canView
     *
     * @dataProvider providerView
     */
    public function testVote(TokenInterface $token, Project $subject, string $attribute, int $expected)
    {
        $voter = static::$container->get(ProjectVoter::class);

        static::assertSame($expected, $voter->vote($token, $subject, (array) $attribute));
    }

    public function providerView()
    {
        static::bootKernel();

        /** @var EntityManagerInterface $em */
        $em = static::$container->get(EntityManagerInterface::class);

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
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW: manager participant cannot view draft project' => [
                'staff_company:bar_user:a',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW: manager participant can view published project' => [
                'staff_company:bar_user:a',
                'published',
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW: borrower cannot view draft project' => [
                'user:€',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW: borrower can view published project' => [
                'user:€',
                'published',
                VoterInterface::ACCESS_GRANTED,
            ],

            'VIEW: connected user with unknown company in project cannot can view draft project' => [
                'staff_company:qux_user:b',
                'draft',
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW: connected user with unknown company cannot can view published project' => [
                'staff_company:qux_user:b',
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

        $projects = $this->fetchEntities($em, Project::class, array_column($tests, 1));

        $staffs = $this->fetchEntities($em, Staff::class, array_column($tests, 0));

        $users = $this->fetchEntities($em, User::class, array_column($tests, 0));

        $tokens = array_map([$this, 'loginStaff'], $staffs);

        $tokens = array_merge($tokens, array_map([$this, 'loginUser'], $users));

        foreach ($tests as $test => [$token, $project, $expected]) {
            yield $test          => [$tokens[$token], $projects[$project], ProjectVoter::ATTRIBUTE_VIEW, $expected];
        }
    }

    private function loginUser(User $user): TokenInterface
    {
        return new UsernamePasswordToken($user, $user->getPassword(), 'api', $user->getRoles());
    }

    private function loginStaff(Staff $staff): TokenInterface
    {
        $user = $staff->getUser();

        $token = $this->loginUser($user);

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
}
