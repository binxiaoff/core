<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210716093006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-4151 Remove finalAllocation field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_participation DROP final_allocation_amount, DROP final_allocation_currency');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_participation ADD final_allocation_amount NUMERIC(15, 2) NOT NULL, ADD final_allocation_currency VARCHAR(3) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
