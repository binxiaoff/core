<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210129172822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3142 [Agency] Add capital to borrowers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower ADD capital_amount NUMERIC(15, 2) NOT NULL, ADD capital_currency VARCHAR(3) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower DROP capital_amount, DROP capital_currency');
    }
}
