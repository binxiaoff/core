<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20180917135418BLD298 extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $createTable = <<<CREATETABLE
CREATE TABLE user_agent(
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    id_client INT(11) NOT NULL,
    browser_name VARCHAR(48),
    browser_version VARCHAR(32),
    device_model VARCHAR(48),
    device_brand VARCHAR(48),
    device_type VARCHAR(32),
    added DATETIME NOT NULL,
    user_agent_string VARCHAR(256) NOT NULL,
    INDEX idx_user_agent_client_browser_device_model_brand_type (id_client, browser_name, device_model, device_brand, device_type),
    CONSTRAINT fk_user_agent_id_client FOREIGN KEY (id_client) REFERENCES clients(id_client) ON UPDATE CASCADE
)
CREATETABLE;
       $this->addSql($createTable);

        $alterTable = <<< ALTERTABLE
ALTER TABLE clients_history
    ADD COLUMN ip VARCHAR(45) AFTER status,
    ADD COLUMN country_iso_code VARCHAR(2) AFTER ip,
    ADD COLUMN city VARCHAR(64) AFTER county_iso_code,
    ADD COLUMN id_user_agent INT(11),
    ADD INDEX idx_clients_history_ip (ip),
    ADD INDEX idx_clients_history_id_user_agent (id_user_agent),
    ADD INDEX idx_clients_history_added (added),
    ADD CONSTRAINT fk_clients_history_id_user_agent FOREIGN KEY (id_user_agent) REFERENCES user_agent(id) ON UPDATE CASCADE
ALTERTABLE;
        $this->addSql($alterTable);

        $this->addSql('ANALYZE TABLE clients_history');
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        $this->addSql('DROP TABLE user_agent');

        $this->addSql('ALTER TABLE clients_history DROP ip, DROP county_iso_code, DROP city, DROP id_user_agent, DROP INDEX idx_clients_history_ip, DROP INDEX idx_clients_history_added, DROP INDEX idx_clients_history_id_user_agent, DROP FOREIGN KEY fk_clients_history_id_user_agent');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');

        $this->addSql('ANALYZE TABLE clients_history');
    }
}
