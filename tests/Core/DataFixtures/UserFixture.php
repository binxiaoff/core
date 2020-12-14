<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker\Provider\Internet;
use ReflectionException;
use Unilend\Core\Entity\User;

class UserFixture extends AbstractFixture
{
    /**
     * @inheritDoc
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        foreach (range(1, 20) as $index) {
            $user = new User('user' . $index . '@' . Internet::safeEmailDomain());

            $reference = 'user/' . $index;

            $this->addReference($reference, $user);

            $this->setPublicId($user, $reference);

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
}
