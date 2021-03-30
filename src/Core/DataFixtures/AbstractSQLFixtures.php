<?php

declare(strict_types=1);

namespace Unilend\Core\DataFixtures;

use Doctrine\Persistence\ObjectManager;

abstract class AbstractSQLFixtures extends AbstractFixtures
{
    protected static string $sql;

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        if (static::$sql) {
            $statement = $manager->getConnection()->prepare(static::$sql);
            $statement->execute();
        }
    }
}
