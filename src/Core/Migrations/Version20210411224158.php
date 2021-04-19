<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210411224158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3694 change capped_at to numeric';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program ADD capped_at NUMERIC(3, 2) DEFAULT NULL, DROP capped_at_amount, DROP capped_at_currency');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program ADD capped_at_amount NUMERIC(15, 2) DEFAULT NULL, ADD capped_at_currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP capped_at');
    }
}
