<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190513154542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-107 Add devise for AcceptedBids';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE accepted_bids CHANGE id_accepted_bid id INT NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE accepted_bids ADD money_amount NUMERIC(10, 2) NOT NULL AFTER id_loan');
        $this->addSql('ALTER TABLE accepted_bids ADD money_currency VARCHAR(3) NOT NULL AFTER money_amount, DROP amount');
        $this->addSql('ALTER TABLE accepted_bids RENAME INDEX idx_accepted_bids_id_bid TO IDX_4B80AF05D4565BA9');
        $this->addSql('ALTER TABLE accepted_bids RENAME INDEX idx_accepted_bids_id_loan TO IDX_4B80AF054EF31101');
        $this->addSql('ALTER TABLE accepted_bids RENAME INDEX unq_accepted_bids_id_bid_id_loan TO UNIQ_4B80AF05D4565BA94EF31101');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE accepted_bids CHANGE id id_accepted_bid INT NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE accepted_bids ADD amount INT NOT NULL AFTER id_loan, DROP money_amount, DROP money_currency');
        $this->addSql('ALTER TABLE accepted_bids RENAME INDEX idx_4b80af054ef31101 TO idx_accepted_bids_id_loan');
        $this->addSql('ALTER TABLE accepted_bids RENAME INDEX uniq_4b80af05d4565ba94ef31101 TO unq_accepted_bids_id_bid_id_loan');
        $this->addSql('ALTER TABLE accepted_bids RENAME INDEX idx_4b80af05d4565ba9 TO idx_accepted_bids_id_bid');
    }
}
