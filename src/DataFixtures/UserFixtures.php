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
    public const OTHER = 'USER_OTHER';

    /**
     * @param ObjectManager $manager
     *
     * @throws \ReflectionException
     */
    public function load(ObjectManager $manager): void
    {
        $admin = $this->createUser('admin@ca-lendingservices.com', 'arranger');
        $other = $this->createUser('other@ca-lendingservices.com', 'other');
        $manager->persist($admin);
        $manager->persist($admin->getCurrentStatus());
        $manager->persist($other);
        $manager->persist($other->getCurrentStatus());
        $manager->flush();
        $this->addReference(self::ADMIN, $admin);
        $this->addReference(self::OTHER, $other);
    }

    /**
     * Create a fake user
     *
     * @param string $email
     * @param string $publicId
     *
     * @return Clients
     *
     * @throws \ReflectionException
     */
    public function createUser(string $email, string $publicId): Clients
    {
        $user = $client = (new Clients($this->faker->company))
            ->setTitle($this->faker->company)
            ->setLastName($this->faker->lastName)
            ->setFirstName($this->faker->firstName)
            ->setPhone('+33600000000')
            ->setMobile('+33600000000')
            ->setJobFunction($this->faker->jobTitle)
            ->setEmail($email)
            ->setPlainPassword('0000');
        $status = new ClientStatus($user, ClientStatus::STATUS_CREATED);
        $user->setCurrentStatus($status);
        $this->forcePublicId($user, $publicId);

        return $user;
    }
}
