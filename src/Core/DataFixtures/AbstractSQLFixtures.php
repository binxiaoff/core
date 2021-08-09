<?php

declare(strict_types=1);

namespace KLS\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;

abstract class AbstractSQLFixtures extends AbstractFixtures
{
    protected static string $sql;

    public function load(ObjectManager $manager): void
    {
        if (static::$sql) {
            $statement = $manager->getConnection()->prepare(static::$sql);
            $statement->execute();
        }
    }
}
