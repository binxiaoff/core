<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210310231632 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-3257 optimise the eligibility tables and add missing unique index for credit_guaranty_program_grade_allocation';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_eligibility_criteria ADD predefined_items JSON DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_10BA42696DE44026CACBFD6F4C70DEF4 ON credit_guaranty_program_choice_option');
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option ADD id_eligibility_criteria INT NOT NULL');
        $this->addSql('UPDATE credit_guaranty_program_choice_option cgpco
                           INNER JOIN credit_guaranty_eligibility_criteria cgec ON cgpco.field_alias = cgec.field_alias
                           SET cgpco.id_eligibility_criteria = cgec.id');
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option DROP field_alias');
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option ADD CONSTRAINT FK_10BA4269F79B4C9A FOREIGN KEY (id_eligibility_criteria) REFERENCES credit_guaranty_eligibility_criteria (id)');
        $this->addSql('CREATE INDEX IDX_10BA4269F79B4C9A ON credit_guaranty_program_choice_option (id_eligibility_criteria)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_10BA42696DE44026F79B4C9A4C70DEF4 ON credit_guaranty_program_choice_option (description, id_eligibility_criteria, id_program)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_20B3F09A4C70DEF4595AAE34 ON credit_guaranty_program_grade_allocation (id_program, grade)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_eligibility_criteria DROP predefined_items');
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option DROP FOREIGN KEY FK_10BA4269F79B4C9A');
        $this->addSql('DROP INDEX IDX_10BA4269F79B4C9A ON credit_guaranty_program_choice_option');
        $this->addSql('DROP INDEX UNIQ_10BA42696DE44026F79B4C9A4C70DEF4 ON credit_guaranty_program_choice_option');
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option ADD field_alias VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP id_eligibility_criteria');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_10BA42696DE44026CACBFD6F4C70DEF4 ON credit_guaranty_program_choice_option (description, field_alias, id_program)');
        $this->addSql('DROP INDEX UNIQ_20B3F09A4C70DEF4595AAE34 ON credit_guaranty_program_grade_allocation');
    }
}
