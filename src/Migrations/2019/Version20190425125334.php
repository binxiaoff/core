<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190425125334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, id_borrower_company INT NOT NULL, id_company_submitter INT NOT NULL, id_client_submitter INT NOT NULL, id_market_segment INT DEFAULT NULL, id_project_status_history INT DEFAULT NULL, hash VARCHAR(191) NOT NULL, slug VARCHAR(191) NOT NULL, title VARCHAR(191) NOT NULL, description MEDIUMTEXT NOT NULL, reply_deadline DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', expected_closing_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', updated DATETIME DEFAULT NULL, added DATETIME NOT NULL, INDEX IDX_2FB3D0EE4C5E290C (id_borrower_company), INDEX IDX_2FB3D0EE24FEBA6C (id_company_submitter), INDEX IDX_2FB3D0EEEE78DD55 (id_client_submitter), INDEX IDX_2FB3D0EE2C71A0E3 (id_market_segment), UNIQUE INDEX UNIQ_2FB3D0EEC60C84FB (id_project_status_history), INDEX slug (slug), INDEX hash (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE market_segment (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(30) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE4C5E290C FOREIGN KEY (id_borrower_company) REFERENCES companies (id_company)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE24FEBA6C FOREIGN KEY (id_company_submitter) REFERENCES companies (id_company)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEEE78DD55 FOREIGN KEY (id_client_submitter) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE2C71A0E3 FOREIGN KEY (id_market_segment) REFERENCES market_segment (id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEC60C84FB FOREIGN KEY (id_project_status_history) REFERENCES projects_status_history (id_project_status_history)');
        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1EF12E799E');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1EF12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_percent_fee DROP FOREIGN KEY FK_F7D17EEFF12E799E');
        $this->addSql('ALTER TABLE project_percent_fee ADD CONSTRAINT FK_F7D17EEFF12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_participant DROP FOREIGN KEY FK_1F509CEAF12E799E');
        $this->addSql('ALTER TABLE project_participant ADD CONSTRAINT FK_1F509CEAF12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_attachment DROP FOREIGN KEY FK_61F9A289F12E799E');
        $this->addSql('ALTER TABLE project_attachment ADD CONSTRAINT FK_61F9A289F12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_attachment RENAME INDEX id_project TO IDX_61F9A289F12E799E');
        $this->addSql('ALTER TABLE project_attachment RENAME INDEX id_attachment TO IDX_61F9A289DCD5596C');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBCF12E799E');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBCF12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');

        $this->addSql("INSERT INTO market_segment (id, label) VALUES (1, 'Collectivité Publique'),
                (2, 'Énergie'),
                (3, 'Corporate'),
                (4, 'LBO'),
                (5, 'Promotion immobilière'),
                (6, 'Infrastructure')");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1EF12E799E');
        $this->addSql('ALTER TABLE project_percent_fee DROP FOREIGN KEY FK_F7D17EEFF12E799E');
        $this->addSql('ALTER TABLE project_participant DROP FOREIGN KEY FK_1F509CEAF12E799E');
        $this->addSql('ALTER TABLE project_attachment DROP FOREIGN KEY FK_61F9A289F12E799E');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBCF12E799E');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE2C71A0E3');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE market_segment');
        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1EF12E799E');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1EF12E799E FOREIGN KEY (id_project) REFERENCES projects (id_project) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBCF12E799E');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBCF12E799E FOREIGN KEY (id_project) REFERENCES projects (id_project) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_attachment DROP FOREIGN KEY FK_61F9A289F12E799E');
        $this->addSql('ALTER TABLE project_attachment ADD CONSTRAINT FK_61F9A289F12E799E FOREIGN KEY (id_project) REFERENCES projects (id_project) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_attachment RENAME INDEX idx_61f9a289f12e799e TO id_project');
        $this->addSql('ALTER TABLE project_attachment RENAME INDEX idx_61f9a289dcd5596c TO id_attachment');
        $this->addSql('ALTER TABLE project_participant DROP FOREIGN KEY FK_1F509CEAF12E799E');
        $this->addSql('ALTER TABLE project_participant ADD CONSTRAINT FK_1F509CEAF12E799E FOREIGN KEY (id_project) REFERENCES projects (id_project) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_percent_fee DROP FOREIGN KEY FK_F7D17EEFF12E799E');
        $this->addSql('ALTER TABLE project_percent_fee ADD CONSTRAINT FK_F7D17EEFF12E799E FOREIGN KEY (id_project) REFERENCES projects (id_project) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
