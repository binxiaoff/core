<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211007133345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4843 change max_allocation_rate properties type to numeric(5, 4)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation CHANGE max_allocation_rate max_allocation_rate NUMERIC(5, 4) NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_program_grade_allocation CHANGE max_allocation_rate max_allocation_rate NUMERIC(5, 4) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation CHANGE max_allocation_rate max_allocation_rate NUMERIC(3, 2) NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_program_grade_allocation CHANGE max_allocation_rate max_allocation_rate NUMERIC(3, 2) NOT NULL');
    }
}
