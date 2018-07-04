<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20180703091613 extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        $this->addSql('INSERT IGNORE INTO translations (locale, section, name, translation, added, updated) VALUES (\'fr_FR\', \'project-request\', \'step-1-position-of-company\', \'%position% de %company%\', NOW(), NOW())');

    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        $this->addSql('DELETE from translations WHERE section =  \'project-request\' AND name = \'step-1-position-of-company\'');
    }
}
