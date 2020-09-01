<?php

namespace Unilend\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use ReflectionClass;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\User;
use Unilend\Entity\Clients;

abstract class AbstractFixtures extends Fixture implements FixtureInterface
{

    protected \Faker\Generator $faker;

    private TokenStorageInterface $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->faker = \Faker\Factory::create('fr_FR');
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param $entity
     * @param string $name
     *
     * @throws \ReflectionException
     */
    protected function forcePublicId($entity, string $name): void
    {
        $ref = new ReflectionClass(get_class($entity));
        $property = $ref->getProperty("publicId");
        $property->setAccessible(true);
        $property->setValue($entity, $name);
    }

    /**
     * Return multiple references
     *
     * @param array $names
     *
     * @return object[]
     */
    protected function getReferences(array $names): array
    {
        return array_map(function (string $name) {
            return $this->getReference($name);
        }, array_combine($names, $names));
    }

    /**
     * @param User|string $user
     */
    protected function login($user): void
    {
        if (is_string($user)) {
            $user = $this->getReference($user);
        }
        $this->tokenStorage->setToken(new JWTUserToken($user->getRoles(), $user));
    }
}
