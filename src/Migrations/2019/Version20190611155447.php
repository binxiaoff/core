<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190611155447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM clients_history WHERE id_user_agent_history IS NOT NULL');
        $this->addSql('DELETE FROM user_agent_history WHERE 1');
        $this->addSql('DROP INDEX idx_user_agent_browser_device_model_brand_type ON user_agent_history');
        $this->addSql('ALTER TABLE user_agent_history ADD id_client INT NOT NULL');
        $this->addSql('ALTER TABLE user_agent_history ADD CONSTRAINT FK_1B67BFB1E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client)');
        $this->addSql('CREATE INDEX IDX_1B67BFB1E173B1B8 ON user_agent_history (id_client)');
        $this->addSql('CREATE INDEX IDX_1B67BFB1E173B1B8D5438ED0111092BEDAD7193F5E78213 ON user_agent_history (id_client, browser_name, device_model, device_brand, device_type)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_agent_history DROP FOREIGN KEY FK_1B67BFB1E173B1B8');
        $this->addSql('DROP INDEX IDX_1B67BFB1E173B1B8 ON user_agent_history');
        $this->addSql('DROP INDEX IDX_1B67BFB1E173B1B8D5438ED0111092BEDAD7193F5E78213 ON user_agent_history');
        $this->addSql('ALTER TABLE user_agent_history DROP id_client');
        $this->addSql('CREATE INDEX idx_user_agent_browser_device_model_brand_type ON user_agent_history (browser_name, device_model, device_brand, device_type)');
    }
}
