<?php

namespace Unilend\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Entity\Clients;
use Unilend\Entity\ClientStatus;

class UserFixtures extends AbstractFixtures
{

    public const ADMIN = 'USER_ADMIN';

    /**
     * @param ObjectManager $manager
     *
     * @throws \ReflectionException
     */
    public function load(ObjectManager $manager): void
    {
        $user = $client = (new Clients($this->faker->company))
            ->setTitle($this->faker->company)
            ->setLastName($this->faker->lastName)
            ->setFirstName($this->faker->firstName)
            ->setPhone($this->faker->phoneNumber)
            ->setMobile($this->faker->phoneNumber)
            ->setJobFunction($this->faker->jobTitle)
            ->setEmail('admin@ca-lendingservices.com')
            ->setPlainPassword('0000');
        $status = new ClientStatus($user, ClientStatus::STATUS_CREATED);
        $manager->persist($status);
        $user->setCurrentStatus($status);
        $this->forcePublicId($user, 'arranger');
        $manager->persist($user);
        $manager->flush();
        $this->addReference(self::ADMIN, $user);
    }
}
