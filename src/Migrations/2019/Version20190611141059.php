<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190611141059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bids CHANGE money_amount money_amount NUMERIC(15, 2) NOT NULL');
        $this->addSql('ALTER TABLE accepted_bids CHANGE money_amount money_amount NUMERIC(15, 2) NOT NULL');
        $this->addSql('ALTER TABLE tranche CHANGE money_amount money_amount NUMERIC(15, 2) NOT NULL');
        $this->addSql('ALTER TABLE loans CHANGE money_amount money_amount NUMERIC(15, 2) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE accepted_bids CHANGE money_amount money_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE bids CHANGE money_amount money_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE loans CHANGE money_amount money_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE tranche CHANGE money_amount money_amount NUMERIC(10, 2) NOT NULL');
    }
}
