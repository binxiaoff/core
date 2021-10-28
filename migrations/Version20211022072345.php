<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211022072345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4944 fix list type condition with multi-select';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE credit_guaranty_program_eligibility_condition_choice_option (program_eligibility_condition_id INT NOT NULL, program_choice_option_id INT NOT NULL, INDEX IDX_9C67A234667EBBBA (program_eligibility_condition_id), INDEX IDX_9C67A234E3F54223 (program_choice_option_id), PRIMARY KEY(program_eligibility_condition_id, program_choice_option_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition_choice_option ADD CONSTRAINT FK_9C67A234667EBBBA FOREIGN KEY (program_eligibility_condition_id) REFERENCES credit_guaranty_program_eligibility_condition (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition_choice_option ADD CONSTRAINT FK_9C67A234E3F54223 FOREIGN KEY (program_choice_option_id) REFERENCES credit_guaranty_program_choice_option (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition DROP FOREIGN KEY FK_F9820BF3CB0F0BCB');
        $this->addSql('DROP INDEX IDX_F9820BF3CB0F0BCB ON credit_guaranty_program_eligibility_condition');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition DROP id_program_choice_option');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE credit_guaranty_program_eligibility_condition_choice_option');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition ADD id_program_choice_option INT DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition ADD CONSTRAINT FK_F9820BF3CB0F0BCB FOREIGN KEY (id_program_choice_option) REFERENCES credit_guaranty_program_choice_option (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_F9820BF3CB0F0BCB ON credit_guaranty_program_eligibility_condition (id_program_choice_option)');
    }
}
