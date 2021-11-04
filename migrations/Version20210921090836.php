<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210921090836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4447 handle template page';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_program_reportingTemplateField_field_reporting_template ON credit_guaranty_reporting_template_field (id_reporting_template, id_field)');
        $this->addSql('ALTER TABLE credit_guaranty_reporting_template_field RENAME INDEX uniq_89d582b3b5b48b91 TO UNIQ_B05A7C81B5B48B91');
        $this->addSql('ALTER TABLE credit_guaranty_reporting_template_field RENAME INDEX idx_89d582b3cc99d7d5 TO IDX_B05A7C81CC99D7D5');
        $this->addSql('ALTER TABLE credit_guaranty_reporting_template_field RENAME INDEX idx_89d582b3b5700468 TO IDX_B05A7C81B5700468');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_program_reportingTemplateField_field_reporting_template ON credit_guaranty_reporting_template_field');
        $this->addSql('ALTER TABLE credit_guaranty_reporting_template_field RENAME INDEX idx_b05a7c81b5700468 TO IDX_89D582B3B5700468');
        $this->addSql('ALTER TABLE credit_guaranty_reporting_template_field RENAME INDEX idx_b05a7c81cc99d7d5 TO IDX_89D582B3CC99D7D5');
        $this->addSql('ALTER TABLE credit_guaranty_reporting_template_field RENAME INDEX uniq_b05a7c81b5b48b91 TO UNIQ_89D582B3B5B48B91');
    }
}
