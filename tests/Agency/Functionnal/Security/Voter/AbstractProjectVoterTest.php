<?php

declare(strict_types=1);

namespace Unilend\Test\Agency\Functionnal\Security\Voter;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;

abstract class AbstractProjectVoterTest extends KernelTestCase
{
    protected function loginUser(User $user): TokenInterface
    {
        // Must use clone because of User::setCurrentStaff
        // Otherwise the successive calls to setCurrentStaff overwrites the last value
        // TODO Remove clone when VoterRefactor (can* calls based on token instead of user) is made
        return new UsernamePasswordToken(clone $user, $user->getPassword(), 'api', $user->getRoles());
    }

    protected function loginStaff(Staff $staff): TokenInterface
    {
        $token = $this->loginUser($staff->getUser());

        /** @var User $user */
        $user = $token->getUser();

        $user->setCurrentStaff($staff);
        $token->setAttribute('staff', $staff);
        $token->setAttribute('company', $staff->getCompany());

        return $token;
    }

    protected function fetchEntities(EntityManagerInterface $em, string $class, array $publicIds = [])
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

    protected function formatProviderData(string $attribute, array $tests = []): iterable
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
