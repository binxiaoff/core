<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191029092109 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-464 (Update client login log)';
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

        $this->addSql('ALTER TABLE client_successful_login RENAME INDEX idx_96557a7e173b1b8 TO IDX_94E06715E173B1B8');
        $this->addSql('ALTER TABLE client_successful_login RENAME INDEX idx_96557a798450d1e TO IDX_94E0671561C3E712');
        $this->addSql('ALTER TABLE client_successful_login RENAME INDEX idx_clients_login_ip TO idx_client_successful_login_ip');
        $this->addSql('ALTER TABLE client_successful_login RENAME INDEX idx_clients_login_added TO idx_client_successful_login_added');
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

        $this->addSql('ALTER TABLE client_successful_login RENAME INDEX idx_94e06715e173b1b8 TO IDX_96557A7E173B1B8');
        $this->addSql('ALTER TABLE client_successful_login RENAME INDEX idx_client_successful_login_added TO idx_clients_login_added');
        $this->addSql('ALTER TABLE client_successful_login RENAME INDEX idx_client_successful_login_ip TO idx_clients_login_ip');
        $this->addSql('ALTER TABLE client_successful_login RENAME INDEX idx_94e0671561c3e712 TO IDX_96557A798450D1E');
    }
}
