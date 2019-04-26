<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException as DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190423101404 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Remove agent from bids and loans';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE bids DROP agent');
        $this->addSql('ALTER TABLE loans DROP agent');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE bids ADD agent TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE loans ADD agent TINYINT(1) NOT NULL');
    }
}
