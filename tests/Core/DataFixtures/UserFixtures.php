<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker\Provider\fr_FR\Person;
use ReflectionException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Entity\UserStatus;

class UserFixtures extends AbstractFixtures
{
    public const DEFAULT_PASSWORD = '0000';

    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(TokenStorageInterface $tokenStorage, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct($tokenStorage);
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * {@inheritDoc}
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        $manager->getConnection()->getConfiguration()->setSQLLogger(null);

        $users = [
            ...array_map([$this, 'createInitializedUser'], range(1, 10)),
            ...array_map([$this, 'createUser'], range(11, 20)), // These users are meant to be unitialized
            ...array_map([$this, 'createInitializedUser'], range('a', 'z')),
            ...array_map([$this, 'createInitializedUser'], ['€', '@', '$', '£', '+']), // These users are meant to be staff-less
        ];

        array_walk($users, [$manager, 'persist']);

        $manager->flush();
    }

    /**
     * @param mixed $identification
     *
     * @throws ReflectionException
     * @throws Exception
     */
    private function createUser($identification): User
    {
        $user = new User('user_' . $identification . '@test.com');

        $reference = 'user:' . $identification;

        $this->addReference($reference, $user);

        $this->setPublicId($user, $reference);

        return $user;
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function createInitializedUser(string $identification): User
    {
        $user = $this->createUser($identification);

        $this->initialize($user);

        return $user;
    }

    /**
     * @return object|string
     */
    public static function getReferenceName(User $user)
    {
        return $user->getPublicId();
    }

    /**
     * @throws Exception
     */
    private function initialize(User $user)
    {
        $user->setFirstName(Person::firstNameMale());
        $user->setLastName(Person::firstNameFemale());
        $user->setPhone('+33600000000');
        $user->setJobFunction('job');
        $user->setPassword($this->passwordEncoder->encodePassword($user, static::DEFAULT_PASSWORD));
        $user->setCurrentStatus(new UserStatus($user, UserStatus::STATUS_CREATED));
    }
}
