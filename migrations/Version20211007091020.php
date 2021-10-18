<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211007091020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4764 set boolean type fields to comparable + change credit_guaranty_program_eligibility_condition value type to string nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE credit_guaranty_field SET comparable = 1 WHERE tag = 'eligibility' AND type = 'bool'");
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition CHANGE value value VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition CHANGE value value NUMERIC(15, 2) NOT NULL');
    }
}
