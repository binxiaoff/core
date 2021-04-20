<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210121175038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2111 [Agency] Add tranches';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agency_borrower_tranche_share (id INT AUTO_INCREMENT NOT NULL, id_borrower INT DEFAULT NULL, id_tranche INT DEFAULT NULL, warranty VARCHAR(40) NOT NULL, public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_1B75C8DDB5B48B91 (public_id), INDEX IDX_1B75C8DD8B4BA121 (id_borrower), INDEX IDX_1B75C8DDB8FAF130 (id_tranche), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE agency_tranche (id INT AUTO_INCREMENT NOT NULL, id_project INT DEFAULT NULL, name VARCHAR(30) NOT NULL, syndicated TINYINT(1) NOT NULL, third_party_syndicate VARCHAR(255) DEFAULT NULL, color VARCHAR(30) DEFAULT NULL, loan_type VARCHAR(30) NOT NULL, repayment_type VARCHAR(30) NOT NULL, duration SMALLINT NOT NULL, commission_type VARCHAR(30) DEFAULT NULL, commission_rate NUMERIC(5, 4) DEFAULT NULL, comment LONGTEXT DEFAULT NULL, validity_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', public_id VARCHAR(36) NOT NULL, money_amount NUMERIC(15, 2) NOT NULL, money_currency VARCHAR(3) NOT NULL, rate_index_type VARCHAR(20) DEFAULT NULL, rate_margin NUMERIC(4, 4) DEFAULT NULL, rate_floor NUMERIC(4, 4) DEFAULT NULL, rate_floor_type VARCHAR(20) DEFAULT NULL, UNIQUE INDEX UNIQ_1067C111B5B48B91 (public_id), INDEX IDX_1067C111F12E799E (id_project), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_borrower_tranche_share ADD CONSTRAINT FK_1B75C8DD8B4BA121 FOREIGN KEY (id_borrower) REFERENCES agency_borrower (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_borrower_tranche_share ADD CONSTRAINT FK_1B75C8DDB8FAF130 FOREIGN KEY (id_tranche) REFERENCES agency_tranche (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_tranche ADD CONSTRAINT FK_1067C111F12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_borrower_tranche_share ADD share_amount NUMERIC(15, 2) NOT NULL, ADD share_currency VARCHAR(3) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1B75C8DD8B4BA121B8FAF130 ON agency_borrower_tranche_share (id_borrower, id_tranche)');
        $this->addSql('ALTER TABLE agency_tranche ADD pull_amount NUMERIC(15, 2) NOT NULL, ADD pull_currency VARCHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE agency_borrower_tranche_share ADD guaranty VARCHAR(40) DEFAULT NULL, DROP warranty');
        $this->addSql('ALTER TABLE agency_tranche CHANGE pull_amount draw_amount NUMERIC(15, 2) DEFAULT NULL, CHANGE pull_currency draw_currency VARCHAR(3) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower_tranche_share ADD warranty VARCHAR(40) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP guaranty');
        $this->addSql('ALTER TABLE agency_tranche CHANGE draw_amount pull_amount NUMERIC(15, 2) NOT NULL, CHANGE draw_currency pull_currency VARCHAR(3) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_borrower_tranche_share DROP FOREIGN KEY FK_1B75C8DDB8FAF130');
        $this->addSql('DROP TABLE agency_borrower_tranche_share');
        $this->addSql('DROP TABLE agency_tranche');
    }
}
