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
    /**
     * @var UserPasswordEncoderInterface
     */
    private UserPasswordEncoderInterface $passwordEncoder;

    /**
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @inheritDoc
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        $manager->getConnection()->getConfiguration()->setSQLLogger(null);
        foreach (range(1, 20) as $index) {
            $user = new User('user' . $index . '@test.com');

            $reference = 'user:' . $index;

            $this->addReference($reference, $user);

            $this->setPublicId($user, $reference);

            if ($index <= 10) {
                $this->initialize($user);
            }

            $manager->persist($user);
        }

        $manager->flush();
    }

    /**
     * @param User $user
     *
     * @return object|string
     */
    public static function getReferenceName(User $user)
    {
        return $user->getPublicId();
    }

    /**
     * @param User $user
     *
     * @throws Exception
     */
    private function initialize(User $user)
    {
        $user->setFirstName(Person::firstNameMale());
        $user->setLastName(Person::firstNameFemale());
        $user->setPhone('+33600000000');
        $user->setJobFunction('job');
        $user->setPassword($this->passwordEncoder->encodePassword($user, '0000'));
        $user->setCurrentStatus(new UserStatus($user, UserStatus::STATUS_CREATED));
    }
}
