<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190619134519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change bid/loan relation with bidder/loaner';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1E5A5F27F2');
        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1EEF7B6696');
        $this->addSql('DROP INDEX IDX_3FF09E1EEF7B6696 ON bids');
        $this->addSql('DROP INDEX IDX_3FF09E1E5A5F27F2 ON bids');
        $this->addSql('ALTER TABLE bids ADD id_lender INT NOT NULL AFTER id_bid, ADD added_by INT NOT NULL, DROP id_autobid, DROP ordre');
        $this->addSql('UPDATE bids b INNER JOIN wallet w ON b.id_wallet = w.id INNER JOIN staff s ON s.id_client = w.id_client SET b.id_lender = s.id_company, b.added_by = w.id_client');
        $this->addSql('ALTER TABLE bids DROP id_wallet');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1E8BB74F6C FOREIGN KEY (id_lender) REFERENCES companies (id_company)');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1E699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client)');
        $this->addSql('CREATE INDEX IDX_3FF09E1E8BB74F6C ON bids (id_lender)');
        $this->addSql('CREATE INDEX IDX_3FF09E1E699B6BAF ON bids (added_by)');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC5A5F27F2');
        $this->addSql('DROP INDEX IDX_82C24DBC5A5F27F2 ON loans');
        $this->addSql('ALTER TABLE loans ADD id_lender INT NOT NULL AFTER id_loan');
        $this->addSql('UPDATE loans l INNER JOIN wallet w ON l.id_wallet = w.id INNER JOIN staff s ON s.id_client = w.id_client SET l.id_lender = s.id_company');
        $this->addSql('ALTER TABLE loans DROP id_wallet');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC8BB74F6C FOREIGN KEY (id_lender) REFERENCES companies (id_company)');
        $this->addSql('CREATE INDEX IDX_82C24DBC8BB74F6C ON loans (id_lender)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1E8BB74F6C');
        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1E699B6BAF');
        $this->addSql('DROP INDEX IDX_3FF09E1E8BB74F6C ON bids');
        $this->addSql('DROP INDEX IDX_3FF09E1E699B6BAF ON bids');
        $this->addSql('ALTER TABLE bids ADD id_wallet INT NOT NULL AFTER id_bid, ADD id_autobid INT DEFAULT NULL AFTER id_bid, ADD ordre INT DEFAULT NULL AFTER status');
        $this->addSql('UPDATE bids b INNER JOIN wallet w ON w.id_client = b.added_by SET b.id_wallet = w.id');
        $this->addSql('ALTER TABLE bids DROP id_lender, DROP added_by');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1E5A5F27F2 FOREIGN KEY (id_wallet) REFERENCES wallet (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1EEF7B6696 FOREIGN KEY (id_autobid) REFERENCES autobid (id_autobid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_3FF09E1EEF7B6696 ON bids (id_autobid)');
        $this->addSql('CREATE INDEX IDX_3FF09E1E5A5F27F2 ON bids (id_wallet)');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC8BB74F6C');
        $this->addSql('DROP INDEX IDX_82C24DBC8BB74F6C ON loans');
        $this->addSql('ALTER TABLE loans ADD id_wallet INT NOT NULL AFTER id_loan');
        $this->addSql('UPDATE loans l INNER JOIN staff s ON l.id_lender = s.id_company INNER JOIN wallet w ON s.id_client = s.id_client SET l.id_wallet = w.id');
        $this->addSql('ALTER TABLE loans DROP id_lender');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC5A5F27F2 FOREIGN KEY (id_wallet) REFERENCES wallet (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_82C24DBC5A5F27F2 ON loans (id_wallet)');
    }
}
