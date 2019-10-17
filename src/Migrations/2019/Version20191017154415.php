<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191017154415 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-456 database structure';
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

        //$this->addSql('ALTER TABLE accepted_bids DROP FOREIGN KEY FK_4B80AF05D4565BA9');
        //$this->addSql('ALTER TABLE bid_fee DROP FOREIGN KEY FK_386AAFFED4565BA9');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAD4565BA9');
        //$this->addSql('ALTER TABLE accepted_bids DROP FOREIGN KEY FK_4B80AF054EF31101');
        //$this->addSql('ALTER TABLE loan_fee DROP FOREIGN KEY FK_8054E1114EF31101');
        $this->addSql('CREATE TABLE zz_versioned_project_offer (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(255) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', username VARCHAR(255) DEFAULT NULL, INDEX IDX_4C2C54E2A78D87A7 (logged_at), INDEX IDX_4C2C54E2F85E0677 (username), INDEX IDX_4C2C54E2232D562B69684D7DBF1CD3C3 (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE zz_versioned_tranche_offer (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(255) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', username VARCHAR(255) DEFAULT NULL, INDEX IDX_38D147AEA78D87A7 (logged_at), INDEX IDX_38D147AEF85E0677 (username), INDEX IDX_38D147AE232D562B69684D7DBF1CD3C3 (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_offer (id INT AUTO_INCREMENT NOT NULL, id_lender INT NOT NULL, id_project INT NOT NULL, added_by INT NOT NULL, updated_by INT DEFAULT NULL, committee_status VARCHAR(30) NOT NULL, expected_committee_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', comment LONGTEXT DEFAULT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3A838EA08BB74F6C (id_lender), INDEX IDX_3A838EA0F12E799E (id_project), INDEX IDX_3A838EA0699B6BAF (added_by), INDEX IDX_3A838EA016FE72E1 (updated_by), UNIQUE INDEX UNIQ_3A838EA0F12E799E8BB74F6C (id_project, id_lender), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tranche_offer_fee (id INT AUTO_INCREMENT NOT NULL, id_tranche_offer INT NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', fee_type VARCHAR(50) NOT NULL, fee_comment LONGTEXT DEFAULT NULL, fee_rate NUMERIC(4, 4) NOT NULL, fee_is_recurring TINYINT(1) NOT NULL, INDEX IDX_92989083F0564C89 (id_tranche_offer), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tranche_offer (id INT AUTO_INCREMENT NOT NULL, id_tranche INT NOT NULL, id_project_offer INT NOT NULL, added_by INT NOT NULL, updated_by INT DEFAULT NULL, status VARCHAR(30) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', rate_index_type VARCHAR(20) NOT NULL, rate_margin NUMERIC(4, 4) NOT NULL, rate_floor NUMERIC(4, 4) DEFAULT NULL, money_amount NUMERIC(15, 2) NOT NULL, money_currency VARCHAR(3) NOT NULL, INDEX IDX_4E7E9DECB8FAF130 (id_tranche), INDEX IDX_4E7E9DEC84AB5FC5 (id_project_offer), INDEX IDX_4E7E9DEC699B6BAF (added_by), INDEX IDX_4E7E9DEC16FE72E1 (updated_by), UNIQUE INDEX UNIQ_4E7E9DECB8FAF13084AB5FC5 (id_tranche, id_project_offer), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_offer ADD CONSTRAINT FK_3A838EA08BB74F6C FOREIGN KEY (id_lender) REFERENCES companies (id_company)');
        $this->addSql('ALTER TABLE project_offer ADD CONSTRAINT FK_3A838EA0F12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_offer ADD CONSTRAINT FK_3A838EA0699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE project_offer ADD CONSTRAINT FK_3A838EA016FE72E1 FOREIGN KEY (updated_by) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE tranche_offer_fee ADD CONSTRAINT FK_92989083F0564C89 FOREIGN KEY (id_tranche_offer) REFERENCES tranche_offer (id)');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DECB8FAF130 FOREIGN KEY (id_tranche) REFERENCES tranche (id)');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC84AB5FC5 FOREIGN KEY (id_project_offer) REFERENCES project_offer (id)');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC16FE72E1 FOREIGN KEY (updated_by) REFERENCES clients (id_client)');
        $this->addSql('DROP TABLE accepted_bids');
        $this->addSql('DROP TABLE bid_fee');
        $this->addSql('DROP TABLE bids');
        $this->addSql('DROP TABLE loan_fee');
        $this->addSql('DROP TABLE loans');
        $this->addSql('DROP TABLE redirections');
        $this->addSql('DROP TABLE zz_versioned_bid');
        $this->addSql('DROP TABLE zz_versioned_loan');
        $this->addSql('DROP INDEX IDX_BF5476CAD4565BA9 ON notification');
        $this->addSql('ALTER TABLE notification CHANGE id_bid id_tranche_offer INT DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAF0564C89 FOREIGN KEY (id_tranche_offer) REFERENCES tranche_offer (id)');
        $this->addSql('CREATE INDEX IDX_BF5476CAF0564C89 ON notification (id_tranche_offer)');
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

        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC84AB5FC5');
        $this->addSql('ALTER TABLE tranche_offer_fee DROP FOREIGN KEY FK_92989083F0564C89');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAF0564C89');
        $this->addSql('CREATE TABLE accepted_bids (id INT AUTO_INCREMENT NOT NULL, id_bid INT NOT NULL, id_loan INT DEFAULT NULL, added_by INT NOT NULL, money_amount NUMERIC(15, 2) NOT NULL, money_currency VARCHAR(3) NOT NULL COLLATE utf8mb4_unicode_ci, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4B80AF05699B6BAF (added_by), INDEX IDX_4B80AF05D4565BA9 (id_bid), UNIQUE INDEX UNIQ_4B80AF05D4565BA94EF31101 (id_bid, id_loan), INDEX IDX_4B80AF054EF31101 (id_loan), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE bid_fee (id INT AUTO_INCREMENT NOT NULL, id_bid INT NOT NULL, fee_type VARCHAR(50) NOT NULL COLLATE utf8mb4_unicode_ci, fee_comment LONGTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, fee_rate NUMERIC(4, 4) NOT NULL, fee_is_recurring TINYINT(1) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_386AAFFED4565BA9 (id_bid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE bids (id_bid INT AUTO_INCREMENT NOT NULL, id_lender INT NOT NULL, id_tranche INT NOT NULL, added_by INT NOT NULL, rate_index_type VARCHAR(20) NOT NULL COLLATE utf8mb4_unicode_ci, rate_margin NUMERIC(4, 4) NOT NULL, rate_floor NUMERIC(4, 4) DEFAULT NULL, money_amount NUMERIC(15, 2) NOT NULL, money_currency VARCHAR(3) NOT NULL COLLATE utf8mb4_unicode_ci, status SMALLINT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', comment LONGTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, expected_committee_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', INDEX IDX_3FF09E1E8BB74F6C (id_lender), INDEX IDX_3FF09E1EB8FAF130 (id_tranche), INDEX IDX_3FF09E1EB8FAF1307B00651C (id_tranche, status), INDEX IDX_3FF09E1E699B6BAF (added_by), PRIMARY KEY(id_bid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE loan_fee (id INT AUTO_INCREMENT NOT NULL, id_loan INT NOT NULL, fee_type VARCHAR(50) NOT NULL COLLATE utf8mb4_unicode_ci, fee_comment LONGTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, fee_rate NUMERIC(4, 4) NOT NULL, fee_is_recurring TINYINT(1) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8054E1114EF31101 (id_loan), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE loans (id_loan INT AUTO_INCREMENT NOT NULL, id_lender INT NOT NULL, id_tranche INT NOT NULL, id_acceptation_legal_doc INT DEFAULT NULL, money_amount NUMERIC(15, 2) NOT NULL, money_currency VARCHAR(3) NOT NULL COLLATE utf8mb4_unicode_ci, rate_index_type VARCHAR(20) NOT NULL COLLATE utf8mb4_unicode_ci, rate_margin NUMERIC(4, 4) NOT NULL, rate_floor NUMERIC(4, 4) DEFAULT NULL, status SMALLINT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_82C24DBCB8FAF130 (id_tranche), INDEX IDX_82C24DBCB8FAF1307B00651C (id_tranche, status), INDEX IDX_82C24DBCC0B7B270 (id_acceptation_legal_doc), INDEX IDX_82C24DBCCBBF90EB (added), INDEX IDX_82C24DBC8BB74F6C (id_lender), PRIMARY KEY(id_loan)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE redirections (id_langue VARCHAR(5) NOT NULL COLLATE utf8mb4_unicode_ci, from_slug VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, to_slug VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, type INT NOT NULL, status SMALLINT NOT NULL, added DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id_langue, from_slug)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE zz_versioned_bid (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL COLLATE utf8mb4_unicode_ci, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci, object_class VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, version INT NOT NULL, data LONGTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:array)\', username VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX IDX_F6A67F78A78D87A7 (logged_at), INDEX IDX_F6A67F78F85E0677 (username), INDEX IDX_F6A67F78232D562B69684D7DBF1CD3C3 (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE zz_versioned_loan (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL COLLATE utf8mb4_unicode_ci, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci, object_class VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, version INT NOT NULL, data LONGTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:array)\', username VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX IDX_BF050367A78D87A7 (logged_at), INDEX IDX_BF050367F85E0677 (username), INDEX IDX_BF050367232D562B69684D7DBF1CD3C3 (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE accepted_bids ADD CONSTRAINT FK_4B80AF054EF31101 FOREIGN KEY (id_loan) REFERENCES loans (id_loan) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE accepted_bids ADD CONSTRAINT FK_4B80AF05699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE accepted_bids ADD CONSTRAINT FK_4B80AF05D4565BA9 FOREIGN KEY (id_bid) REFERENCES bids (id_bid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE bid_fee ADD CONSTRAINT FK_386AAFFED4565BA9 FOREIGN KEY (id_bid) REFERENCES bids (id_bid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1E699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1E8BB74F6C FOREIGN KEY (id_lender) REFERENCES companies (id_company) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1EB8FAF130 FOREIGN KEY (id_tranche) REFERENCES tranche (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE loan_fee ADD CONSTRAINT FK_8054E1114EF31101 FOREIGN KEY (id_loan) REFERENCES loans (id_loan) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC8BB74F6C FOREIGN KEY (id_lender) REFERENCES companies (id_company) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBCB8FAF130 FOREIGN KEY (id_tranche) REFERENCES tranche (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBCC0B7B270 FOREIGN KEY (id_acceptation_legal_doc) REFERENCES acceptations_legal_docs (id_acceptation) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE zz_versioned_project_offer');
        $this->addSql('DROP TABLE zz_versioned_tranche_offer');
        $this->addSql('DROP TABLE project_offer');
        $this->addSql('DROP TABLE tranche_offer_fee');
        $this->addSql('DROP TABLE tranche_offer');
        $this->addSql('DROP INDEX IDX_BF5476CAF0564C89 ON notification');
        $this->addSql('ALTER TABLE notification CHANGE id_tranche_offer id_bid INT DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAD4565BA9 FOREIGN KEY (id_bid) REFERENCES bids (id_bid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_BF5476CAD4565BA9 ON notification (id_bid)');
    }
}
