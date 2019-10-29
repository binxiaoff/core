<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191024155946 extends AbstractMigration
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
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_agent_history RENAME TO user_agent');
        $this->addSql('ALTER TABLE user_agent RENAME INDEX idx_1b67bfb1e173b1b8 TO IDX_C44967C5E173B1B8');
        $this->addSql('ALTER TABLE user_agent RENAME INDEX idx_1b67bfb1e173b1b8d5438ed0111092bedad7193f5e78213 TO IDX_C44967C5E173B1B8D5438ED0111092BEDAD7193F5E78213');
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

        $this->addSql('ALTER TABLE user_agent RENAME TO user_agent_history');
        $this->addSql('ALTER TABLE user_agent_history RENAME INDEX idx_c44967c5e173b1b8d5438ed0111092bedad7193f5e78213 TO IDX_1B67BFB1E173B1B8D5438ED0111092BEDAD7193F5E78213');
        $this->addSql('ALTER TABLE user_agent_history RENAME INDEX idx_c44967c5e173b1b8 TO IDX_1B67BFB1E173B1B8');
    }
}
