<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190614073711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALSTECH-37 Add DC2Type:datetime_immutable for impacted tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bids CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE accepted_bids CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE bid_fee CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE tranche CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE loans CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE project_fee CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE attachment_signature CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE companies CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE fee_type CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE tranche_fee CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE attachment CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE project_participant CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE project_attachment CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE clients CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE clients_history CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE staff CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE project_status_history CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE projects CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE product CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_agent_history CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE repayment_type CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE loan_fee CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE underlying_contract CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE interest_rate_index_type CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE project_comment CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE project CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE accepted_bids CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE attachment CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE attachment_signature CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE bid_fee CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE bids CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE clients CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE clients_history CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE companies CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE fee_type CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE interest_rate_index_type CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE loan_fee CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE loans CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE project CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE project_attachment CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE project_comment CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE project_fee CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE project_participant CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE project_status_history CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE projects CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE repayment_type CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE staff CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tranche CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tranche_fee CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE underlying_contract CHANGE updated updated DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user_agent_history CHANGE added added DATETIME NOT NULL');
    }
}
