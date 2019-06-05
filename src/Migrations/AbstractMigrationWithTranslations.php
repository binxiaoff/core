<?php

declare(strict_types=1);

namespace Unilend\Migrations;

use Doctrine\DBAL\Schema\Schema;

abstract class AbstractMigrationWithTranslations extends ContainerAwareMigration
{
    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema): void
    {
        $this->container->get('sonata.cache.symfony')->flush(['translations']);
    }

    /**
     * @param Schema $schema
     */
    public function postDown(Schema $schema): void
    {
        $this->container->get('sonata.cache.symfony')->flush(['translations']);
    }
}
