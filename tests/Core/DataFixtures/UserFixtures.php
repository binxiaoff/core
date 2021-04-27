<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker\Provider\fr_FR\Person;
use ReflectionException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Entity\UserStatus;

class UserFixtures extends AbstractFixtures
{
    public const DEFAULT_PASSWORD = '0000';

    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
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
        foreach (range(1, 25) as $index) {
            $user = new User('user' . $index . '@test.com');

            $reference = 'user:' . $index;

            $this->addReference($reference, $user);

            $this->setPublicId($user, $reference);

            $this->initialize($user);

            $manager->persist($user);
        }

        $user = new User('user_uninitialized@test.com');

        $reference = 'user:uninitialized';

        $this->addReference($reference, $user);

        $this->setPublicId($user, $reference);

        $manager->persist($user);

        $manager->flush();
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
