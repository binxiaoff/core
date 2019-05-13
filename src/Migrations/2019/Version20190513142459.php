<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190513142459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-107 Add money to bid and loan';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bids ADD money_amount NUMERIC(10, 2) NOT NULL AFTER ordre');
        $this->addSql('ALTER TABLE bids ADD money_currency VARCHAR(3) NOT NULL AFTER money_amount, DROP amount');
        $this->addSql('ALTER TABLE tranche CHANGE money_currency money_currency VARCHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE loans ADD money_amount NUMERIC(10, 2) NOT NULL AFTER id_type_contract');
        $this->addSql('ALTER TABLE loans ADD money_currency VARCHAR(3) NOT NULL AFTER money_amount, DROP amount');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bids ADD amount INT NOT NULL, DROP money_amount, DROP money_currency');
        $this->addSql('ALTER TABLE loans ADD amount INT NOT NULL, DROP money_amount, DROP money_currency');
        $this->addSql('ALTER TABLE tranche CHANGE money_currency money_currency VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci');
    }
}
