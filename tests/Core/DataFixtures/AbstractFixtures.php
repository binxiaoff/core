<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use ReflectionClass;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;

abstract class AbstractFixtures extends Fixture implements FixtureGroupInterface
{
    private array $publicIdReflexionProperties = [];

    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritDoc}
     */
    public static function getGroups(): array
    {
        return ['test'];
    }

    /**
     * @param $object
     *
     * @throws \ReflectionException
     */
    protected function setPublicId($object, string $publicId)
    {
        /** @var string $class */
        $class = \get_class($object);

        if (false === isset($this->publicIdReflexionProperties[$class])) {
            $reflectionClass = new ReflectionClass($class);

            if ($reflectionClass->hasProperty('publicId')) {
                $property = $reflectionClass->getProperty('publicId');
                $property->setAccessible(true);
                $this->publicIdReflexionProperties[$class] = $property;
            }
        }

        if (($property = ($this->publicIdReflexionProperties[$class] ?? null))) {
            $property->setValue($object, $publicId);
        }
    }

    protected function loginUser(User $user)
    {
        $token = new UsernamePasswordToken($user, $user->getPassword(), 'main');

        $this->tokenStorage->setToken($token);

        return $token;
    }

    protected function loginStaff(Staff $staff)
    {
        $token = $this->loginUser($staff->getUser());

        $staff->getUser()->setCurrentStaff($staff);
        $token->setAttribute('staff', $staff);
        $token->setAttribute('company', $staff->getCompany());

        return $token;
    }

    /**
     * @param array|string[]|string $events
     */
    protected function removeEventListenerByClass(ObjectManager $manager, $events, string $class)
    {
        if ($manager instanceof EntityManagerInterface) {
            $eventManager = $manager->getEventManager();
            foreach ((array) $events as $event) {
                foreach ($eventManager->getListeners($event) as $listener) {
                    if ($listener instanceof $class) {
                        $eventManager->removeEventListener($event, $listener);
                    }
                }
            }
        }
    }
}
