<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210630143954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4050 add max_fei_credit fields in credit_guaranty_program';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program ADD max_fei_credit_amount NUMERIC(15, 2) DEFAULT NULL, ADD max_fei_credit_currency VARCHAR(3) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program DROP max_fei_credit_amount, DROP max_fei_credit_currency');
    }
}
