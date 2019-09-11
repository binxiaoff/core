<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190905082214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'TECH-85';
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

        $this->addSql('ALTER TABLE bids CHANGE rate_margin rate_margin DECIMAL(7, 4) NOT NULL, CHANGE rate_floor rate_floor DECIMAL(7, 4) DEFAULT NULL');
        $this->addSql('UPDATE bids SET rate_margin = CAST(rate_margin / 100.00 as DECIMAL(4, 4)), rate_floor = CAST(rate_floor / 100.00 as DECIMAL(4, 4))');
        $this->addSql('ALTER TABLE bids CHANGE rate_margin rate_margin DECIMAL(4, 4) NOT NULL, CHANGE rate_floor rate_floor DECIMAL(4, 4) DEFAULT NULL');

        $this->addSql('ALTER TABLE loan_fee CHANGE fee_rate fee_rate DECIMAL(7, 4) NOT NULL');
        $this->addSql('UPDATE loan_fee SET fee_rate = CAST(fee_rate / 100.00 as DECIMAL(4, 4))');
        $this->addSql('ALTER TABLE loan_fee CHANGE fee_rate fee_rate DECIMAL(4, 4) NOT NULL');

        $this->addSql('ALTER TABLE loans CHANGE rate_margin rate_margin DECIMAL(7, 4) NOT NULL, CHANGE rate_floor rate_floor DECIMAL(7, 4) DEFAULT NULL');
        $this->addSql('UPDATE loans SET rate_margin = CAST(rate_margin / 100.00 as DECIMAL(4, 4)), rate_floor = CAST(rate_floor / 100.00 as DECIMAL(4, 4))');
        $this->addSql('ALTER TABLE loans CHANGE rate_margin rate_margin DECIMAL(4, 4) NOT NULL, CHANGE rate_floor rate_floor DECIMAL(4, 4) DEFAULT NULL');

        $this->addSql('ALTER TABLE bid_fee CHANGE fee_rate fee_rate DECIMAL(7, 4) NOT NULL');
        $this->addSql('UPDATE bid_fee SET fee_rate = CAST(fee_rate / 100.00 as DECIMAL(4, 4))');
        $this->addSql('ALTER TABLE bid_fee CHANGE fee_rate fee_rate DECIMAL(4, 4) NOT NULL');

        $this->addSql('ALTER TABLE project_fee CHANGE fee_rate fee_rate DECIMAL(7, 4) NOT NULL');
        $this->addSql('UPDATE project_fee SET fee_rate = CAST(fee_rate / 100.00 as DECIMAL(4, 4))');
        $this->addSql('ALTER TABLE project_fee CHANGE fee_rate fee_rate DECIMAL(4, 4) NOT NULL');

        $this->addSql('ALTER TABLE tranche CHANGE rate_margin rate_margin DECIMAL(7, 4) DEFAULT NULL, CHANGE rate_floor rate_floor DECIMAL(7, 4) DEFAULT NULL');
        $this->addSql('UPDATE tranche SET rate_margin = CAST(rate_margin / 100.00 as DECIMAL(4, 4)), rate_floor = CAST(rate_floor / 100.00 as DECIMAL(4, 4))');
        $this->addSql('ALTER TABLE tranche CHANGE rate_margin rate_margin DECIMAL(4, 4) DEFAULT NULL, CHANGE rate_floor rate_floor DECIMAL(4, 4) DEFAULT NULL');

        $this->addSql('ALTER TABLE tranche_fee CHANGE fee_rate fee_rate DECIMAL(7, 4) NOT NULL');
        $this->addSql('UPDATE tranche_fee SET  fee_rate = CAST(fee_rate / 100.00 as DECIMAL(4, 4))');
        $this->addSql('ALTER TABLE tranche_fee CHANGE fee_rate fee_rate DECIMAL(4, 4) NOT NULL');
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

        $this->addSql('ALTER TABLE bid_fee CHANGE fee_rate fee_rate DECIMAL(7, 4) NOT NULL');
        $this->addSql('UPDATE bid_fee SET fee_rate = CAST(fee_rate * 100.00 as DECIMAL(4,2))');
        $this->addSql('ALTER TABLE bid_fee CHANGE fee_rate fee_rate DECIMAL(4, 2) NOT NULL');

        $this->addSql('ALTER TABLE bids CHANGE rate_margin rate_margin DECIMAL(7, 4) NOT NULL, CHANGE rate_floor rate_floor DECIMAL(7, 4) DEFAULT NULL');
        $this->addSql('UPDATE bids SET rate_margin = CAST(rate_margin * 100.00 as DECIMAL(4, 2)), rate_floor = CAST(rate_floor * 100.00 as DECIMAL(4, 2))');
        $this->addSql('ALTER TABLE bids CHANGE rate_margin rate_margin DECIMAL(4, 2) NOT NULL, CHANGE rate_floor rate_floor DECIMAL(4, 2) DEFAULT NULL');

        $this->addSql('ALTER TABLE loan_fee CHANGE fee_rate fee_rate DECIMAL(7, 4) NOT NULL');
        $this->addSql('UPDATE loan_fee SET fee_rate = CAST(fee_rate * 100.00 as DECIMAL(4, 2))');
        $this->addSql('ALTER TABLE loan_fee CHANGE fee_rate fee_rate DECIMAL(4, 2) NOT NULL');

        $this->addSql('ALTER TABLE loans CHANGE rate_margin rate_margin DECIMAL(7, 4) NOT NULL, CHANGE rate_floor rate_floor DECIMAL(7, 4) DEFAULT NULL');
        $this->addSql('UPDATE loans SET rate_margin = CAST(rate_margin * 100.00 as DECIMAL(4,2)), rate_floor = CAST(rate_floor * 100.00 as DECIMAL(4,2))');
        $this->addSql('ALTER TABLE loans CHANGE rate_margin rate_margin DECIMAL(4, 2) NOT NULL, CHANGE rate_floor rate_floor DECIMAL(4, 2) DEFAULT NULL');

        $this->addSql('ALTER TABLE project_fee CHANGE fee_rate fee_rate DECIMAL(7, 4) NOT NULL');
        $this->addSql('UPDATE project_fee SET fee_rate = CAST(fee_rate * 100.00 as DECIMAL(4, 2))');
        $this->addSql('ALTER TABLE project_fee CHANGE fee_rate fee_rate DECIMAL(4, 2) NOT NULL');

        $this->addSql('ALTER TABLE tranche CHANGE rate_margin rate_margin DECIMAL(7, 4) DEFAULT NULL, CHANGE rate_floor rate_floor DECIMAL(7, 4) DEFAULT NULL');
        $this->addSql('UPDATE tranche SET rate_margin = CAST(rate_margin * 100.00 as DECIMAL(4, 2)), rate_floor = CAST(rate_floor * 100.00 as DECIMAL(4, 2))');
        $this->addSql('ALTER TABLE tranche CHANGE rate_margin rate_margin DECIMAL(4, 2) DEFAULT NULL, CHANGE rate_floor rate_floor DECIMAL(4, 2) DEFAULT NULL');

        $this->addSql('ALTER TABLE tranche_fee CHANGE fee_rate fee_rate DECIMAL(7, 4) NOT NULL');
        $this->addSql('UPDATE tranche_fee SET  fee_rate = CAST(fee_rate * 100.00 as DECIMAL(4, 2))');
        $this->addSql('ALTER TABLE tranche_fee CHANGE fee_rate fee_rate DECIMAL(4, 2) NOT NULL');
    }
}
