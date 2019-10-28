<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191024143007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-463 (Update client login history)';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE clients_history CHANGE status action VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE clients_history RENAME TO client_login');
        $this->addSql('ALTER TABLE client_login CHANGE id_history id INT NOT NULL');
        $this->addSql('ALTER TABLE client_login CHANGE id id INT AUTO_INCREMENT NOT NULL ');
        $this->addSql('ALTER TABLE client_login RENAME INDEX idx_19d1d044e173b1b8 TO IDX_96557A7E173B1B8');
        $this->addSql('ALTER TABLE client_login RENAME INDEX idx_19d1d04498450d1e TO IDX_96557A798450D1E');
        $this->addSql('ALTER TABLE client_login RENAME INDEX idx_clients_history_ip TO idx_clients_login_ip');
        $this->addSql('ALTER TABLE client_login RENAME INDEX idx_clients_history_added TO idx_clients_login_added');
        $this->addSql('ALTER TABLE clients DROP last_login');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE client_login CHANGE id id_history INT NOT NULL ');
        $this->addSql('ALTER TABLE client_login CHANGE id_history id_history INT AUTO_INCREMENT NOT NULL ');
        $this->addSql('ALTER TABLE client_login RENAME INDEX idx_96557a7e173b1b8 TO IDX_19D1D044E173B1B8');
        $this->addSql('ALTER TABLE client_login RENAME INDEX idx_clients_login_added TO idx_clients_history_added');
        $this->addSql('ALTER TABLE client_login RENAME INDEX idx_clients_login_ip TO idx_clients_history_ip');
        $this->addSql('ALTER TABLE client_login RENAME INDEX idx_96557a798450d1e TO IDX_19D1D04498450D1E');

        $this->addSql('ALTER TABLE client_login RENAME TO clients_history');
        $this->addSql("UPDATE clients_history SET action = '1'");
        $this->addSql('ALTER TABLE clients_history CHANGE action status INT NOT NULL');
        $this->addSql('ALTER TABLE clients ADD last_login DATETIME DEFAULT NULL');
    }
}
