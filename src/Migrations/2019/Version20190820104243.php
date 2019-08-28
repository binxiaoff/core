<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190820104243 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'TECH-77 Migrate queries to Doctrine entity';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE queries CHANGE paging paging INT DEFAULT 100 NOT NULL, CHANGE executions executions INT DEFAULT 0 NOT NULL, CHANGE executed executed DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE `sql` query MEDIUMTEXT NOT NULL');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE queries CHANGE paging paging INT NOT NULL, CHANGE executions executions INT NOT NULL, CHANGE executed executed DATETIME NOT NULL, CHANGE updated updated DATETIME NOT NULL, CHANGE added added DATETIME NOT NULL, CHANGE query `sql` MEDIUMTEXT NOT NULL COLLATE utf8mb4_unicode_ci');
    }
}
