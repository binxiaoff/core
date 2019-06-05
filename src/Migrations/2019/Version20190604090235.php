<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\AbstractMigrationWithTranslations;

final class Version20190604090235 extends AbstractMigrationWithTranslations
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Rename user_agent to user_agent_history';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE clients_history DROP FOREIGN KEY FK_19D1D04461C3E712');
        $this->addSql('CREATE TABLE user_agent_history (id INT AUTO_INCREMENT NOT NULL, id_client INT NOT NULL, browser_name VARCHAR(48) DEFAULT NULL, browser_version VARCHAR(32) DEFAULT NULL, device_model VARCHAR(48) DEFAULT NULL, device_brand VARCHAR(48) DEFAULT NULL, device_type VARCHAR(32) DEFAULT NULL, user_agent_string VARCHAR(256) NOT NULL, added DATETIME NOT NULL, INDEX IDX_1B67BFB1E173B1B8 (id_client), INDEX idx_user_agent_client_browser_device_model_brand_type (id_client, browser_name, device_model, device_brand, device_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_agent_history ADD CONSTRAINT FK_1B67BFB1E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client)');
        $this->addSql('DROP TABLE user_agent');
        $this->addSql('DROP INDEX idx_clients_history_id_user_agent ON clients_history');
        $this->addSql('ALTER TABLE clients_history CHANGE id_user_agent id_user_agent_history INT DEFAULT NULL');
        $this->addSql('ALTER TABLE clients_history ADD CONSTRAINT FK_19D1D04498450D1E FOREIGN KEY (id_user_agent_history) REFERENCES user_agent_history (id)');
        $this->addSql('CREATE INDEX IDX_19D1D04498450D1E ON clients_history (id_user_agent_history)');
        $this->addSql('ALTER TABLE clients_history RENAME INDEX id_client TO IDX_19D1D044E173B1B8');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE clients_history DROP FOREIGN KEY FK_19D1D04498450D1E');
        $this->addSql('CREATE TABLE user_agent (id INT AUTO_INCREMENT NOT NULL, id_client INT NOT NULL, browser_name VARCHAR(48) DEFAULT NULL COLLATE utf8mb4_unicode_ci, browser_version VARCHAR(32) DEFAULT NULL COLLATE utf8mb4_unicode_ci, device_model VARCHAR(48) DEFAULT NULL COLLATE utf8mb4_unicode_ci, device_brand VARCHAR(48) DEFAULT NULL COLLATE utf8mb4_unicode_ci, device_type VARCHAR(32) DEFAULT NULL COLLATE utf8mb4_unicode_ci, user_agent_string VARCHAR(256) NOT NULL COLLATE utf8mb4_unicode_ci, added DATETIME NOT NULL, INDEX idx_user_agent_client_browser_device_model_brand_type (id_client, browser_name, device_model, device_brand, device_type), INDEX IDX_C44967C5E173B1B8 (id_client), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE user_agent ADD CONSTRAINT FK_C44967C5E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE user_agent_history');
        $this->addSql('DROP INDEX IDX_19D1D04498450D1E ON clients_history');
        $this->addSql('ALTER TABLE clients_history CHANGE id_user_agent_history id_user_agent INT DEFAULT NULL');
        $this->addSql('ALTER TABLE clients_history ADD CONSTRAINT FK_19D1D04461C3E712 FOREIGN KEY (id_user_agent) REFERENCES user_agent (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX idx_clients_history_id_user_agent ON clients_history (id_user_agent)');
        $this->addSql('ALTER TABLE clients_history RENAME INDEX idx_19d1d044e173b1b8 TO id_client');
    }
}
