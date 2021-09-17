<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210917140157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4435 add added_by field in credit_guaranty_reporting_template + add unique constraint on id_program and name';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reporting_template ADD added_by INT NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_reporting_template ADD CONSTRAINT FK_EE2FC26A699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id)');
        $this->addSql('CREATE INDEX IDX_EE2FC26A699B6BAF ON credit_guaranty_reporting_template (added_by)');
        $this->addSql('CREATE UNIQUE INDEX uniq_program_reportingTemplate_name ON credit_guaranty_reporting_template (id_program, name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reporting_template DROP FOREIGN KEY FK_EE2FC26A699B6BAF');
        $this->addSql('DROP INDEX IDX_EE2FC26A699B6BAF ON credit_guaranty_reporting_template');
        $this->addSql('ALTER TABLE credit_guaranty_reporting_template DROP added_by');
        $this->addSql('DROP INDEX uniq_program_reportingTemplate_name ON credit_guaranty_reporting_template');
    }
}
