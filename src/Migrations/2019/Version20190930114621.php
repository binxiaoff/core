<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190930114621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-378';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE temporary_links_login RENAME INDEX fk_temporary_links_login_id_client TO fk_temporary_token_id_client');
        $this->addSql('ALTER TABLE temporary_links_login RENAME COLUMN id_link TO id');
        $this->addSql(
            <<<'SQL'
ALTER TABLE temporary_links_login 
  CHANGE expires expires DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  CHANGE accessed accessed DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', 
  CHANGE added added DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', 
  CHANGE updated updated DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
SQL
        );
        $this->addSql('ALTER TABLE temporary_links_login RENAME TO temporary_token');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE temporary_token RENAME INDEX fk_temporary_token_id_client TO fk_temporary_links_login_id_client');
        $this->addSql('ALTER TABLE temporary_token RENAME COLUMN id TO id_link');
        $this->addSql(
            <<<'SQL'
ALTER TABLE temporary_token
  CHANGE expires expires DATETIME NOT NULL,
  CHANGE accessed accessed DATETIME DEFAULT NULL, 
  CHANGE updated updated DATETIME DEFAULT NULL,
  CHANGE added added DATETIME NOT NULL
SQL
        );
        $this->addSql('ALTER TABLE temporary_token RENAME TO temporary_links_login');
    }
}
