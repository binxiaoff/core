<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201124145115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2844 Remove unused entities';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ca_regional_bank');
        $this->addSql('DROP TABLE foncaris_funding_type');
        $this->addSql('DROP TABLE foncaris_request');
        $this->addSql('DROP TABLE foncaris_security');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE queries');
        $this->addSql('DROP TABLE settings');
        $this->addSql('DROP TABLE tranche_attribute');
        $this->addSql('DROP TABLE translations');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ca_regional_bank (id INT AUTO_INCREMENT NOT NULL, id_company INT NOT NULL, friendly_group INT NOT NULL, UNIQUE INDEX UNIQ_1F07A1669122A03F (id_company), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE foncaris_funding_type (id INT AUTO_INCREMENT NOT NULL, category SMALLINT NOT NULL, description VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE foncaris_request (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, choice SMALLINT DEFAULT NULL, relative_file_path VARCHAR(191) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_BF3AEF05F12E799E (id_project), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE foncaris_security (id INT AUTO_INCREMENT NOT NULL, category SMALLINT NOT NULL, description VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, id_client INT NOT NULL, id_project INT DEFAULT NULL, id_project_participation_tranche INT DEFAULT NULL, type SMALLINT NOT NULL, status SMALLINT NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BF5476CAE173B1B8 (id_client), INDEX IDX_BF5476CAF12E799E (id_project), INDEX IDX_BF5476CAF263895D (id_project_participation_tranche), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE queries (id_query INT AUTO_INCREMENT NOT NULL, name VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, query MEDIUMTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, paging INT DEFAULT 100 NOT NULL, executions INT DEFAULT 0 NOT NULL, executed DATETIME DEFAULT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id_query)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE settings (id_setting INT AUTO_INCREMENT NOT NULL, type VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, value MEDIUMTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_E545A0C58CDE5729 (type), PRIMARY KEY(id_setting)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tranche_attribute (id INT AUTO_INCREMENT NOT NULL, id_tranche INT NOT NULL, attribute_name VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, attribute_value VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D29E7A555CBDA8E (attribute_name), INDEX IDX_D29E7A55B8FAF130 (id_tranche), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE translations (id_translation INT AUTO_INCREMENT NOT NULL, locale VARCHAR(5) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, section VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, name VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, translation TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, added DATETIME NOT NULL, updated DATETIME DEFAULT NULL, INDEX section (section), UNIQUE INDEX unq_translation (locale, section, name), PRIMARY KEY(id_translation)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE ca_regional_bank ADD CONSTRAINT FK_1F07A1669122A03F FOREIGN KEY (id_company) REFERENCES company (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE foncaris_request ADD CONSTRAINT FK_BF3AEF05F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAF12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAF263895D FOREIGN KEY (id_project_participation_tranche) REFERENCES project_participation_tranche (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche_attribute ADD CONSTRAINT FK_D29E7A55B8FAF130 FOREIGN KEY (id_tranche) REFERENCES tranche (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
