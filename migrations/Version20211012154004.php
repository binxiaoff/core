<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211012154004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4776 transform credit_guaranty_project id_investment_thematic to multiple + update investment_thematic field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE credit_guaranty_project_investment_thematic (project_id INT NOT NULL, program_choice_option_id INT NOT NULL, INDEX IDX_BA2C39DE166D1F9C (project_id), INDEX IDX_BA2C39DEE3F54223 (program_choice_option_id), PRIMARY KEY(project_id, program_choice_option_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_project_investment_thematic ADD CONSTRAINT FK_BA2C39DE166D1F9C FOREIGN KEY (project_id) REFERENCES credit_guaranty_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE credit_guaranty_project_investment_thematic ADD CONSTRAINT FK_BA2C39DEE3F54223 FOREIGN KEY (program_choice_option_id) REFERENCES credit_guaranty_program_choice_option (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D02534A9AA5');
        $this->addSql('DROP INDEX IDX_A452D02534A9AA5 ON credit_guaranty_project');
        $this->addSql('ALTER TABLE credit_guaranty_project DROP id_investment_thematic');
        $this->addSql('UPDATE credit_guaranty_field SET property_path = \'investmentThematics\', property_type = \'Collection\' WHERE field_alias = \'investment_thematic\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE credit_guaranty_project_investment_thematic');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD id_investment_thematic INT DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D02534A9AA5 FOREIGN KEY (id_investment_thematic) REFERENCES credit_guaranty_program_choice_option (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_A452D02534A9AA5 ON credit_guaranty_project (id_investment_thematic)');
        $this->addSql('UPDATE credit_guaranty_field SET property_path = \'investmentThematic\', property_type = \'ProgramChoiceOption\' WHERE field_alias = \'investment_thematic\'');
    }
}
