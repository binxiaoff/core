<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Functional\Security\Voter;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Repository\StaffRepository;
use Unilend\Core\Security\Voter\StaffVoter;

/**
 * @coversDefaultClass StaffVoter
 */
class StaffVoterTest extends KernelTestCase
{
    protected array $fixtures;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
    }

    /**
     * @covers ::vote
     *
     * @param string         $attribute
     * @param TokenInterface $connectedToken
     * @param Staff          $subject
     * @param int            $expected
     *
     * @dataProvider providerVote
     */
    public function testVote(
        string $attribute,
        TokenInterface $connectedToken,
        Staff $subject,
        int $expected
    ) {
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = static::$container->get(TokenStorageInterface::class);
        $tokenStorage->setToken($connectedToken);

        $voter = static::$container->get(StaffVoter::class);

        static::assertSame($voter->vote($connectedToken, $subject, [$attribute]), $expected);
    }

    /**
     * @return array[]
     */
    public function providerVote(): array
    {
        static::bootKernel();

        /** @var StaffRepository $repository */
        $repository = static::$container->get(StaffRepository::class);

        $fixtures = $repository->createQueryBuilder('s', 's.publicId')->getQuery()->getResult();

        return array_merge(
            $this->providerView($fixtures),
            $this->providerEdit($fixtures)
        );
    }

    /**
     * @param $staffs
     *
     * @return array[]
     */
    private function providerEdit($staffs): array
    {
        return [
            'EDIT : non manager connected staff cannot edit staff' => [
                StaffVoter::ATTRIBUTE_EDIT,
                $this->createToken($staffs['staff_company/basic_user/6']),
                $staffs['staff_company/basic_user/6'],
                VoterInterface::ACCESS_DENIED,
            ],
            'EDIT : manager can edit staff of its managed team' => [
                StaffVoter::ATTRIBUTE_EDIT,
                $this->createToken($staffs['staff_company/basic_user/5']),
                $staffs['staff_company/basic_user/8'],
                VoterInterface::ACCESS_GRANTED,
            ],
            'EDIT : manager can edit self' => [
                StaffVoter::ATTRIBUTE_EDIT,
                $this->createToken($staffs['staff_company/basic_user/2']),
                $staffs['staff_company/basic_user/2'],
                VoterInterface::ACCESS_GRANTED,
            ],
            'EDIT : manager cannot edit staff outside of its managed team' => [
                StaffVoter::ATTRIBUTE_EDIT,
                $this->createToken($staffs['staff_company/basic_user/2']),
                $staffs['staff_company/basic_user/12'],
                VoterInterface::ACCESS_DENIED,
            ],
            'EDIT : admin staff can edit any staff' => [
                StaffVoter::ATTRIBUTE_EDIT,
                $this->createToken($staffs['staff_company/basic_user/12']),
                $staffs['staff_company/basic_user/8'],
                VoterInterface::ACCESS_GRANTED,
            ],
        ];
    }

    /**
     * @param $staff
     *
     * @return array[]
     */
    private function providerView($staff)
    {
        return [
            'VIEW : non manager connected staff can view own staff' => [
                StaffVoter::ATTRIBUTE_VIEW,
                $this->createToken($staff['staff_company/basic_user/6']),
                $staff['staff_company/basic_user/6'],
                VoterInterface::ACCESS_GRANTED,
            ],
            'VIEW : non manager cannot view staff not is own' => [
                StaffVoter::ATTRIBUTE_VIEW,
                $this->createToken($staff['staff_company/basic_user/6']),
                $staff['staff_company/basic_user/7'],
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW : manager can view staff of its managed team' => [
                StaffVoter::ATTRIBUTE_VIEW,
                $this->createToken($staff['staff_company/basic_user/5']),
                $staff['staff_company/basic_user/8'],
                VoterInterface::ACCESS_GRANTED,
            ],
            'VIEW : manager cannot view staff outside of its managed team' => [
                StaffVoter::ATTRIBUTE_VIEW,
                $this->createToken($staff['staff_company/basic_user/2']),
                $staff['staff_company/basic_user/12'],
                VoterInterface::ACCESS_DENIED,
            ],
            'VIEW : admin staff can view any staff' => [
                StaffVoter::ATTRIBUTE_VIEW,
                $this->createToken($staff['staff_company/basic_user/12']),
                $staff['staff_company/basic_user/8'],
                VoterInterface::ACCESS_GRANTED,
            ],
        ];
    }

    /**
     * @param Staff $staff
     *
     * @return TokenInterface
     */
    private function createToken(Staff $staff): TokenInterface
    {
        $user = $staff->getUser();
        $user->setCurrentStaff($staff);

        return new JWTUserToken($user->getRoles(), $user);
    }
}
