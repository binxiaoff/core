<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190611135625 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bid_percent_fee DROP FOREIGN KEY FK_CBDCCAB1270C44E3');
        $this->addSql('ALTER TABLE loan_percent_fee DROP FOREIGN KEY FK_9BDFD650270C44E3');
        $this->addSql('ALTER TABLE project_percent_fee DROP FOREIGN KEY FK_F7D17EEF270C44E3');
        $this->addSql('ALTER TABLE tranche_percent_fee DROP FOREIGN KEY FK_8FFF575C270C44E3');
        $this->addSql('CREATE TABLE project_fee (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, fee_type SMALLINT NOT NULL, fee_comment LONGTEXT DEFAULT NULL, fee_rate NUMERIC(4, 2) NOT NULL, fee_is_recurring TINYINT(1) NOT NULL, updated DATETIME DEFAULT NULL, added DATETIME NOT NULL, INDEX IDX_432BE56F12E799E (id_project), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bid_fee (id INT AUTO_INCREMENT NOT NULL, id_bid INT NOT NULL, fee_type SMALLINT NOT NULL, fee_comment LONGTEXT DEFAULT NULL, fee_rate NUMERIC(4, 2) NOT NULL, fee_is_recurring TINYINT(1) NOT NULL, updated DATETIME DEFAULT NULL, added DATETIME NOT NULL, INDEX IDX_386AAFFED4565BA9 (id_bid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tranche_fee (id INT AUTO_INCREMENT NOT NULL, id_tranche INT NOT NULL, fee_type SMALLINT NOT NULL, fee_comment LONGTEXT DEFAULT NULL, fee_rate NUMERIC(4, 2) NOT NULL, fee_is_recurring TINYINT(1) NOT NULL, updated DATETIME DEFAULT NULL, added DATETIME NOT NULL, INDEX IDX_ACF46377B8FAF130 (id_tranche), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE loan_fee (id INT AUTO_INCREMENT NOT NULL, id_loan INT NOT NULL, fee_type SMALLINT NOT NULL, fee_comment LONGTEXT DEFAULT NULL, fee_rate NUMERIC(4, 2) NOT NULL, fee_is_recurring TINYINT(1) NOT NULL, updated DATETIME DEFAULT NULL, added DATETIME NOT NULL, INDEX IDX_8054E1114EF31101 (id_loan), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_fee ADD CONSTRAINT FK_432BE56F12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE bid_fee ADD CONSTRAINT FK_386AAFFED4565BA9 FOREIGN KEY (id_bid) REFERENCES bids (id_bid)');
        $this->addSql('ALTER TABLE tranche_fee ADD CONSTRAINT FK_ACF46377B8FAF130 FOREIGN KEY (id_tranche) REFERENCES tranche (id)');
        $this->addSql('ALTER TABLE loan_fee ADD CONSTRAINT FK_8054E1114EF31101 FOREIGN KEY (id_loan) REFERENCES loans (id_loan)');
        $this->addSql('DROP TABLE bid_percent_fee');
        $this->addSql('DROP TABLE loan_percent_fee');
        $this->addSql('DROP TABLE percent_fee');
        $this->addSql('DROP TABLE project_percent_fee');
        $this->addSql('DROP TABLE tranche_percent_fee');

        $this->addSql(
            <<<'TRANSLATIONS'
INSERT INTO translations (locale, section, name, translation, added)
VALUES
  ('fr_FR', 'fee-type', 'tranche_fee_type_commitment', 'Engagement', NOW()),
  ('fr_FR', 'fee-type', 'tranche_fee_type_utilisation', 'Utilisation', NOW()),
  ('fr_FR', 'fee-type', 'tranche_fee_type_non_utilisation', 'Non utilisation', NOW()),
  ('fr_FR', 'fee-type', 'project_fee_type_participation', 'Participation', NOW()),
  ('fr_FR', 'project-request', 'fees-section-title', 'Frais ou commissions', NOW()),
  ('fr_FR', 'project-edit', 'fees-section-title', 'Frais ou commissions', NOW())
TRANSLATIONS
        );

        $this->addSql('UPDATE translations SET section = \'fee-form\' WHERE section = \'percent-fee-form\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE bid_percent_fee (id INT AUTO_INCREMENT NOT NULL, id_percent_fee INT NOT NULL, id_bid INT NOT NULL, UNIQUE INDEX UNIQ_CBDCCAB1270C44E3 (id_percent_fee), INDEX IDX_CBDCCAB1D4565BA9 (id_bid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE loan_percent_fee (id INT AUTO_INCREMENT NOT NULL, id_percent_fee INT NOT NULL, id_loan INT NOT NULL, UNIQUE INDEX UNIQ_9BDFD650270C44E3 (id_percent_fee), INDEX IDX_9BDFD6504EF31101 (id_loan), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE percent_fee (id INT AUTO_INCREMENT NOT NULL, id_type SMALLINT NOT NULL, customised_name VARCHAR(60) DEFAULT NULL COLLATE utf8mb4_unicode_ci, rate NUMERIC(4, 2) NOT NULL, is_recurring TINYINT(1) NOT NULL, added DATETIME NOT NULL, updated DATETIME DEFAULT NULL, INDEX IDX_3F1E89147FE4B2B (id_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE project_percent_fee (id INT AUTO_INCREMENT NOT NULL, id_percent_fee INT NOT NULL, id_project INT NOT NULL, UNIQUE INDEX UNIQ_F7D17EEF270C44E3 (id_percent_fee), INDEX IDX_F7D17EEFF12E799E (id_project), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tranche_percent_fee (id INT AUTO_INCREMENT NOT NULL, id_percent_fee INT NOT NULL, id_tranche INT NOT NULL, UNIQUE INDEX UNIQ_8FFF575C270C44E3 (id_percent_fee), INDEX IDX_8FFF575CB8FAF130 (id_tranche), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE bid_percent_fee ADD CONSTRAINT FK_CBDCCAB1270C44E3 FOREIGN KEY (id_percent_fee) REFERENCES percent_fee (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE bid_percent_fee ADD CONSTRAINT FK_CBDCCAB1D4565BA9 FOREIGN KEY (id_bid) REFERENCES bids (id_bid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE loan_percent_fee ADD CONSTRAINT FK_9BDFD650270C44E3 FOREIGN KEY (id_percent_fee) REFERENCES percent_fee (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE loan_percent_fee ADD CONSTRAINT FK_9BDFD6504EF31101 FOREIGN KEY (id_loan) REFERENCES loans (id_loan) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE percent_fee ADD CONSTRAINT FK_3F1E89147FE4B2B FOREIGN KEY (id_type) REFERENCES fee_type (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_percent_fee ADD CONSTRAINT FK_F7D17EEF270C44E3 FOREIGN KEY (id_percent_fee) REFERENCES percent_fee (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_percent_fee ADD CONSTRAINT FK_F7D17EEFF12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche_percent_fee ADD CONSTRAINT FK_8FFF575C270C44E3 FOREIGN KEY (id_percent_fee) REFERENCES percent_fee (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche_percent_fee ADD CONSTRAINT FK_8FFF575CB8FAF130 FOREIGN KEY (id_tranche) REFERENCES tranche (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE project_fee');
        $this->addSql('DROP TABLE bid_fee');
        $this->addSql('DROP TABLE tranche_fee');
        $this->addSql('DROP TABLE loan_fee');

        $this->addSql('DELETE FROM translations WHERE section = \'fee-type\' AND name in (\'tranche_fee_type_commitment\', \'tranche_fee_type_utilisation\', \'tranche_fee_type_non_utilisation\', \'project_fee_type_participation\')');
        $this->addSql('DELETE FROM translations WHERE section = \'project-request\' AND name = \'fees-section-title\')');

        $this->addSql('UPDATE translations SET section = \'percent-fee-form\' WHERE section = \'fee-form\'');
    }
}
