<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211011164849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4774 set list type fields to comparable + add id_program_choice_option property in credit_guaranty_program_eligibility_condition table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE credit_guaranty_field SET comparable = 1 WHERE tag = 'eligibility' AND type = 'list'");
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition ADD id_program_choice_option INT DEFAULT NULL, CHANGE value value VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition ADD CONSTRAINT FK_F9820BF3CB0F0BCB FOREIGN KEY (id_program_choice_option) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('CREATE INDEX IDX_F9820BF3CB0F0BCB ON credit_guaranty_program_eligibility_condition (id_program_choice_option)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition DROP FOREIGN KEY FK_F9820BF3CB0F0BCB');
        $this->addSql('DROP INDEX IDX_F9820BF3CB0F0BCB ON credit_guaranty_program_eligibility_condition');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition DROP id_program_choice_option, CHANGE value value NUMERIC(15, 2) NOT NULL');
    }
}
