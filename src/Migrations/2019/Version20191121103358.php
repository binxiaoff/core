<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191121103358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM tranche_offer_fee');
        $this->addSql('DELETE FROM tranche_offer');
        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC84AB5FC5');
        $this->addSql('CREATE TABLE project_participation_offer (id INT AUTO_INCREMENT NOT NULL, id_project_participation INT NOT NULL, added_by INT NOT NULL, updated_by INT DEFAULT NULL, committee_status VARCHAR(30) NOT NULL, expected_committee_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', comment LONGTEXT DEFAULT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1C090985AE73E249 (id_project_participation), INDEX IDX_1C090985699B6BAF (added_by), INDEX IDX_1C09098516FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C090985AE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id)');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C090985699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C09098516FE72E1 FOREIGN KEY (updated_by) REFERENCES clients (id_client)');
        $this->addSql('DROP TABLE project_offer');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC84AB5FC5 FOREIGN KEY (id_project_offer) REFERENCES project_participation_offer (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC84AB5FC5');
        $this->addSql('CREATE TABLE project_offer (id INT AUTO_INCREMENT NOT NULL, id_lender INT NOT NULL, id_project INT NOT NULL, added_by INT NOT NULL, updated_by INT DEFAULT NULL, committee_status VARCHAR(30) NOT NULL COLLATE utf8mb4_unicode_ci, expected_committee_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', comment LONGTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3A838EA0699B6BAF (added_by), INDEX IDX_3A838EA08BB74F6C (id_lender), INDEX IDX_3A838EA0F12E799E (id_project), INDEX IDX_3A838EA016FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE project_offer ADD CONSTRAINT FK_3A838EA016FE72E1 FOREIGN KEY (updated_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_offer ADD CONSTRAINT FK_3A838EA0699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_offer ADD CONSTRAINT FK_3A838EA08BB74F6C FOREIGN KEY (id_lender) REFERENCES companies (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_offer ADD CONSTRAINT FK_3A838EA0F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE project_participation_offer');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC84AB5FC5 FOREIGN KEY (id_project_offer) REFERENCES project_offer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
