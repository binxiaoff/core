<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180827120248TECH505 extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("
            INSERT INTO translations (locale, section, name, translation, added, updated) VALUES
              ('fr_FR', 'common', 'day', '{0,1} %count% jour|]1,Inf[  %count% jours', NOW(), NOW()),
              ('fr_FR', 'common', 'hour', '{0,1} %count% heure|]1,Inf[  %count% heures', NOW(), NOW()),
              ('fr_FR', 'common', 'minute', '{0,1} %count% minute|]1,Inf[  %count% minutes', NOW(), NOW()),
              ('fr_FR', 'common', 'second', '{0,1} %count% seconde|]1,Inf[  %count% secondes', NOW(), NOW())
        ");

    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DELETE FROM translations WHERE section = 'common' AND name IN ('day', 'hour', 'minute', 'second')");
    }
}
