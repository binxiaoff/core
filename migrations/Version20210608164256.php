<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210608164256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix the column name of investment thematic';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D025CB0F0BCB');
        $this->addSql('DROP INDEX IDX_A452D025CB0F0BCB ON credit_guaranty_project');
        $this->addSql('ALTER TABLE credit_guaranty_project CHANGE id_program_choice_option id_investment_thematic INT NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D02534A9AA5 FOREIGN KEY (id_investment_thematic) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('CREATE INDEX IDX_A452D02534A9AA5 ON credit_guaranty_project (id_investment_thematic)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D02534A9AA5');
        $this->addSql('DROP INDEX IDX_A452D02534A9AA5 ON credit_guaranty_project');
        $this->addSql('ALTER TABLE credit_guaranty_project CHANGE id_investment_thematic id_program_choice_option INT NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D025CB0F0BCB FOREIGN KEY (id_program_choice_option) REFERENCES credit_guaranty_program_choice_option (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_A452D025CB0F0BCB ON credit_guaranty_project (id_program_choice_option)');
    }
}
