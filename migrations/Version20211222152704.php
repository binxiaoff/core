<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211222152704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-5218 drop unique constraint on credit_guaranty_reporting_template table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_program_reportingTemplate_name ON credit_guaranty_reporting_template');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_program_reportingTemplate_name ON credit_guaranty_reporting_template (id_program, name)');
    }
}
