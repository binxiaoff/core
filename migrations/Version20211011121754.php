<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211011121754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4737 change credit_guaranty_program guaranty_cost money to numeric(5, 4)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program ADD guaranty_cost NUMERIC(5, 4) DEFAULT NULL, DROP guaranty_cost_amount, DROP guaranty_cost_currency');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program ADD guaranty_cost_amount NUMERIC(15, 2) DEFAULT NULL, ADD guaranty_cost_currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP guaranty_cost');
    }
}
