<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Exception;
use ReflectionException;
use Unilend\Entity\Clients;
use Unilend\Entity\ClientStatus;

class UserFixtures extends AbstractFixtures
{

    public const ADMIN = 'USER_ADMIN';
    public const PARTICIPANT = 'USER_PARTICIPANT';
    public const AUDITOR = 'AUDITOR';
    public const INVITED = 'INVITED';
    public const MANAGER = 'MANAGER';

    /**
     * @param ObjectManager $manager
     *
     * @throws ReflectionException
     */
    public function load(ObjectManager $manager): void
    {
        $this->createAndPersistUser('admin@ca-lendingservices.com', 'arranger', self::ADMIN, $manager, false);
        $this->createAndPersistUser('participant@ca-lendingservices.com', 'participant', self::PARTICIPANT, $manager, false);
        $this->createAndPersistUser('auditor@ca-lendingservices.com', 'auditor', self::AUDITOR, $manager, false);
        $this->createAndPersistUser('invited@ca-lendingservices.com', 'invited', self::INVITED, $manager, true);
        $this->createAndPersistUser('manager@ca-lendingservices.com', 'manager', self::MANAGER, $manager, false);

        $manager->flush();
    }

    /**
     * Create a fake user
     *
     * @param string        $email
     * @param string        $publicId
     * @param string        $reference
     * @param ObjectManager $manager
     * @param boolean       $initialized
     *
     * @return Clients
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function createAndPersistUser(string $email, string $publicId, string $reference, ObjectManager $manager, bool $initialized): Clients
    {
        $user = new Clients($email);
        if (!$initialized) {
            $user
                ->setTitle($this->faker->company)
                ->setLastName($this->faker->lastName)
                ->setFirstName($this->faker->firstName)
                ->setPhone('+33600000000')
                ->setMobile('+33600000000')
                ->setJobFunction('Job function')
                ->setEmail($email)
                ->setPlainPassword('0000');
        }
        $status = new ClientStatus($user, $initialized ? ClientStatus::STATUS_INVITED : ClientStatus::STATUS_CREATED);
        $user->setCurrentStatus($status);
        $this->forcePublicId($user, $publicId);

        $manager->persist($user);
        $manager->persist($user->getCurrentStatus());
        $this->addReference($reference, $user);

        return $user;
    }
}
