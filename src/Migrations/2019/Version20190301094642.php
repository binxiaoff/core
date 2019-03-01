<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190301094642 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-24 Add fees to bids and loans';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE loan_fee (id INT AUTO_INCREMENT NOT NULL, id_loan INT NOT NULL, id_lending_fee_type INT DEFAULT NULL, rate NUMERIC(4, 2) NOT NULL, customised_name VARCHAR(60) DEFAULT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_8054E1114EF31101 (id_loan), INDEX IDX_8054E111C0842F73 (id_lending_fee_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE interest_rate_index_type (id SMALLINT AUTO_INCREMENT NOT NULL, label VARCHAR(30) NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', UNIQUE INDEX UNIQ_E3FFC7B7EA750E8 (label), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lending_fee_type (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(30) NOT NULL, is_recurring TINYINT(1) NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', UNIQUE INDEX UNIQ_230E7AA2EA750E8 (label), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bid_fee (id INT AUTO_INCREMENT NOT NULL, id_bid INT NOT NULL, id_lending_fee_type INT DEFAULT NULL, rate NUMERIC(4, 2) NOT NULL, customised_name VARCHAR(60) DEFAULT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_386AAFFED4565BA9 (id_bid), INDEX IDX_386AAFFEC0842F73 (id_lending_fee_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE loan_fee ADD CONSTRAINT FK_8054E1114EF31101 FOREIGN KEY (id_loan) REFERENCES loans (id_loan)');
        $this->addSql('ALTER TABLE loan_fee ADD CONSTRAINT FK_8054E111C0842F73 FOREIGN KEY (id_lending_fee_type) REFERENCES lending_fee_type (id)');
        $this->addSql('ALTER TABLE bid_fee ADD CONSTRAINT FK_386AAFFED4565BA9 FOREIGN KEY (id_bid) REFERENCES bids (id_bid)');
        $this->addSql('ALTER TABLE bid_fee ADD CONSTRAINT FK_386AAFFEC0842F73 FOREIGN KEY (id_lending_fee_type) REFERENCES lending_fee_type (id)');
        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1EFD80A5F3');
        $this->addSql('DROP INDEX id_lender_account ON bids');
        $this->addSql('ALTER TABLE bids ADD id_interest_rate_index_type SMALLINT DEFAULT NULL, CHANGE rate rate NUMERIC(4, 2) NOT NULL, CHANGE id_lender_account id_wallet INT NOT NULL');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1E5A5F27F2 FOREIGN KEY (id_wallet) REFERENCES wallet (id)');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1E88FAFD94 FOREIGN KEY (id_interest_rate_index_type) REFERENCES interest_rate_index_type (id)');
        $this->addSql('CREATE INDEX IDX_3FF09E1E5A5F27F2 ON bids (id_wallet)');
        $this->addSql('CREATE INDEX IDX_3FF09E1E88FAFD94 ON bids (id_interest_rate_index_type)');
        $this->addSql('ALTER TABLE bids RENAME INDEX idx_id_autobid TO IDX_3FF09E1EEF7B6696');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC8BB74F6C');
        $this->addSql('DROP INDEX id_lender ON loans');
        $this->addSql('ALTER TABLE loans ADD id_interest_rate_index_type SMALLINT DEFAULT NULL, CHANGE rate rate NUMERIC(4, 2) NOT NULL, CHANGE id_lender id_wallet INT NOT NULL');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC5A5F27F2 FOREIGN KEY (id_wallet) REFERENCES wallet (id)');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC88FAFD94 FOREIGN KEY (id_interest_rate_index_type) REFERENCES interest_rate_index_type (id)');
        $this->addSql('CREATE INDEX IDX_82C24DBC5A5F27F2 ON loans (id_wallet)');
        $this->addSql('CREATE INDEX IDX_82C24DBC88FAFD94 ON loans (id_interest_rate_index_type)');
        $this->addSql('ALTER TABLE loans RENAME INDEX idx_loans_id_type_contract TO IDX_82C24DBC9A58DEC0');
        $this->addSql('ALTER TABLE loans RENAME INDEX id_project TO IDX_82C24DBCF12E799E');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1E88FAFD94');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC88FAFD94');
        $this->addSql('ALTER TABLE loan_fee DROP FOREIGN KEY FK_8054E111C0842F73');
        $this->addSql('ALTER TABLE bid_fee DROP FOREIGN KEY FK_386AAFFEC0842F73');
        $this->addSql('DROP TABLE loan_fee');
        $this->addSql('DROP TABLE interest_rate_index_type');
        $this->addSql('DROP TABLE lending_fee_type');
        $this->addSql('DROP TABLE bid_fee');
        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1E5A5F27F2');
        $this->addSql('DROP INDEX IDX_3FF09E1E5A5F27F2 ON bids');
        $this->addSql('DROP INDEX IDX_3FF09E1E88FAFD94 ON bids');
        $this->addSql('ALTER TABLE bids DROP id_interest_rate_index_type, CHANGE rate rate NUMERIC(3, 1) NOT NULL, CHANGE id_wallet id_lender_account INT NOT NULL');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1EFD80A5F3 FOREIGN KEY (id_lender_account) REFERENCES wallet (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX id_lender_account ON bids (id_lender_account)');
        $this->addSql('ALTER TABLE bids RENAME INDEX idx_3ff09e1eef7b6696 TO idx_id_autobid');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC5A5F27F2');
        $this->addSql('DROP INDEX IDX_82C24DBC5A5F27F2 ON loans');
        $this->addSql('DROP INDEX IDX_82C24DBC88FAFD94 ON loans');
        $this->addSql('ALTER TABLE loans DROP id_interest_rate_index_type, CHANGE rate rate NUMERIC(3, 1) NOT NULL, CHANGE id_wallet id_lender INT NOT NULL');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC8BB74F6C FOREIGN KEY (id_lender) REFERENCES wallet (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX id_lender ON loans (id_lender)');
        $this->addSql('ALTER TABLE loans RENAME INDEX idx_82c24dbcf12e799e TO id_project');
        $this->addSql('ALTER TABLE loans RENAME INDEX idx_82c24dbc9a58dec0 TO idx_loans_id_type_contract');
    }
}
