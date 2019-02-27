<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190227102641 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-24 Add new attribute to bids and loans';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE InterestRateIndexType (id SMALLINT AUTO_INCREMENT NOT NULL, name VARCHAR(30) NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1EFD80A5F3');
        $this->addSql('DROP INDEX id_lender_account ON bids');
        $this->addSql('ALTER TABLE bids ADD rate_index_type SMALLINT DEFAULT NULL, ADD preciput NUMERIC(4, 2) NOT NULL, ADD administrationFee NUMERIC(4, 2) NOT NULL, ADD setUpFee NUMERIC(4, 2) NOT NULL, CHANGE rate rate NUMERIC(4, 2) NOT NULL, CHANGE id_lender_account id_wallet INT NOT NULL');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1E5A5F27F2 FOREIGN KEY (id_wallet) REFERENCES wallet (id)');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1E8DF5C561 FOREIGN KEY (rate_index_type) REFERENCES InterestRateIndexType (id)');
        $this->addSql('CREATE INDEX IDX_3FF09E1E5A5F27F2 ON bids (id_wallet)');
        $this->addSql('CREATE INDEX IDX_3FF09E1E8DF5C561 ON bids (rate_index_type)');
        $this->addSql('ALTER TABLE bids RENAME INDEX idx_id_autobid TO IDX_3FF09E1EEF7B6696');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC8BB74F6C');
        $this->addSql('DROP INDEX id_lender ON loans');
        $this->addSql('ALTER TABLE loans ADD rate_index_type SMALLINT DEFAULT NULL, ADD preciput NUMERIC(4, 2) NOT NULL, ADD administrationFee NUMERIC(4, 2) NOT NULL, ADD setUpFee NUMERIC(4, 2) NOT NULL, CHANGE rate rate NUMERIC(4, 2) NOT NULL, CHANGE id_lender id_wallet INT NOT NULL');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC5A5F27F2 FOREIGN KEY (id_wallet) REFERENCES wallet (id)');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC8DF5C561 FOREIGN KEY (rate_index_type) REFERENCES InterestRateIndexType (id)');
        $this->addSql('CREATE INDEX IDX_82C24DBC5A5F27F2 ON loans (id_wallet)');
        $this->addSql('CREATE INDEX IDX_82C24DBC8DF5C561 ON loans (rate_index_type)');
        $this->addSql('ALTER TABLE loans RENAME INDEX idx_loans_id_type_contract TO IDX_82C24DBC9A58DEC0');
        $this->addSql('ALTER TABLE loans RENAME INDEX id_project TO IDX_82C24DBCF12E799E');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1E8DF5C561');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC8DF5C561');
        $this->addSql('DROP TABLE InterestRateIndexType');
        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1E5A5F27F2');
        $this->addSql('DROP INDEX IDX_3FF09E1E5A5F27F2 ON bids');
        $this->addSql('DROP INDEX IDX_3FF09E1E8DF5C561 ON bids');
        $this->addSql('ALTER TABLE bids DROP rate_index_type, DROP preciput, DROP administrationFee, DROP setUpFee, CHANGE rate rate NUMERIC(3, 1) NOT NULL, CHANGE id_wallet id_lender_account INT NOT NULL');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1EFD80A5F3 FOREIGN KEY (id_lender_account) REFERENCES wallet (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX id_lender_account ON bids (id_lender_account)');
        $this->addSql('ALTER TABLE bids RENAME INDEX idx_3ff09e1eef7b6696 TO idx_id_autobid');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC5A5F27F2');
        $this->addSql('DROP INDEX IDX_82C24DBC5A5F27F2 ON loans');
        $this->addSql('DROP INDEX IDX_82C24DBC8DF5C561 ON loans');
        $this->addSql('ALTER TABLE loans DROP rate_index_type, DROP preciput, DROP administrationFee, DROP setUpFee, CHANGE rate rate NUMERIC(3, 1) NOT NULL, CHANGE id_wallet id_lender INT NOT NULL');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC8BB74F6C FOREIGN KEY (id_lender) REFERENCES wallet (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX id_lender ON loans (id_lender)');
        $this->addSql('ALTER TABLE loans RENAME INDEX idx_82c24dbcf12e799e TO id_project');
        $this->addSql('ALTER TABLE loans RENAME INDEX idx_82c24dbc9a58dec0 TO idx_loans_id_type_contract');
    }
}
