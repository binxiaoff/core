<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211011102644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4814 change credit_guaranty_program guaranty_coverage property type to numeric(5, 4)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program CHANGE guaranty_coverage guaranty_coverage NUMERIC(5, 4) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program CHANGE guaranty_coverage guaranty_coverage NUMERIC(4, 4) DEFAULT NULL');
    }
}
