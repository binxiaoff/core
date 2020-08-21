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
    public const PARTICIPANT = 'USER_PARTICIPANT';

    /**
     * @param ObjectManager $manager
     *
     * @throws \ReflectionException
     */
    public function load(ObjectManager $manager): void
    {
        $admin = $this->createUser('admin@ca-lendingservices.com', 'arranger');
        $participant = $this->createUser('participant@ca-lendingservices.com', 'participant');
        $manager->persist($admin);
        $manager->persist($admin->getCurrentStatus());
        $manager->persist($participant);
        $manager->persist($participant->getCurrentStatus());
        $manager->flush();
        $this->addReference(self::ADMIN, $admin);
        $this->addReference(self::PARTICIPANT, $participant);
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
            ->setJobFunction('Job function')
            ->setEmail($email)
            ->setPlainPassword('0000');
        $status = new ClientStatus($user, ClientStatus::STATUS_CREATED);
        $user->setCurrentStatus($status);
        $this->forcePublicId($user, $publicId);

        return $user;
    }
}
