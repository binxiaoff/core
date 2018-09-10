<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180904142955 extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('DROP INDEX fk_company_client_id_client ON company_client');
        $this->addSql('CREATE UNIQUE INDEX unq_company_client_id_client ON company_client (id_client)');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');

    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('DROP INDEX unq_company_client_id_client ON company_client');
        $this->addSql('CREATE INDEX fk_company_client_id_client ON company_client (id_client)');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');

    }
}
