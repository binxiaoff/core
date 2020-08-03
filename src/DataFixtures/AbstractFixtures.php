<?php

namespace Unilend\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use ReflectionClass;
use Unilend\Entity\Clients;

abstract class AbstractFixtures extends Fixture implements FixtureInterface
{

    protected \Faker\Generator $faker;

    /**
     */
    public function __construct()
    {
        $this->faker = \Faker\Factory::create('fr_FR');
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
    protected function getReferences(array $names)
    {
        return array_map(function (string $name) {
            return $this->getReference($name);
        }, $names);
    }
}
