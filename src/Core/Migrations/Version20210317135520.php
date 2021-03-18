<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210317135520 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Rename TABLE credit_guaranty_eligibility_criteria to credit_guaranty_field';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option DROP FOREIGN KEY FK_10BA4269F79B4C9A');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility DROP FOREIGN KEY FK_E17147BBF79B4C9A');
        $this->addSql('RENAME TABLE credit_guaranty_eligibility_criteria to credit_guaranty_field');
        $this->addSql('ALTER TABLE credit_guaranty_field RENAME INDEX uniq_f91b38b0b5b48b91 TO UNIQ_E65C1761B5B48B91');
        $this->addSql('DROP INDEX IDX_10BA4269F79B4C9A ON credit_guaranty_program_choice_option');
        $this->addSql('DROP INDEX UNIQ_10BA42696DE44026F79B4C9A4C70DEF4 ON credit_guaranty_program_choice_option');
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option CHANGE id_eligibility_criteria id_field INT NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option ADD CONSTRAINT FK_10BA4269B5700468 FOREIGN KEY (id_field) REFERENCES credit_guaranty_field (id)');
        $this->addSql('CREATE INDEX IDX_10BA4269B5700468 ON credit_guaranty_program_choice_option (id_field)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_10BA42696DE44026B57004684C70DEF4 ON credit_guaranty_program_choice_option (description, id_field, id_program)');
        $this->addSql('DROP INDEX IDX_E17147BBF79B4C9A ON credit_guaranty_program_eligibility');
        $this->addSql('DROP INDEX UNIQ_E17147BBF79B4C9A4C70DEF4 ON credit_guaranty_program_eligibility');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility CHANGE id_eligibility_criteria id_field INT NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility ADD CONSTRAINT FK_E17147BBB5700468 FOREIGN KEY (id_field) REFERENCES credit_guaranty_field (id)');
        $this->addSql('CREATE INDEX IDX_E17147BBB5700468 ON credit_guaranty_program_eligibility (id_field)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E17147BBB57004684C70DEF4 ON credit_guaranty_program_eligibility (id_field, id_program)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option DROP FOREIGN KEY FK_10BA4269B5700468');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility DROP FOREIGN KEY FK_E17147BBB5700468');
        $this->addSql('ALTER TABLE credit_guaranty_field RENAME INDEX uniq_e65c1761b5b48b91 TO UNIQ_F91B38B0B5B48B91');
        $this->addSql('RENAME TABLE credit_guaranty_field to credit_guaranty_eligibility_criteria');
        $this->addSql('DROP INDEX IDX_10BA4269B5700468 ON credit_guaranty_program_choice_option');
        $this->addSql('DROP INDEX UNIQ_10BA42696DE44026B57004684C70DEF4 ON credit_guaranty_program_choice_option');
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option CHANGE id_field id_eligibility_criteria INT NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option ADD CONSTRAINT FK_10BA4269F79B4C9A FOREIGN KEY (id_eligibility_criteria) REFERENCES credit_guaranty_eligibility_criteria (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_10BA4269F79B4C9A ON credit_guaranty_program_choice_option (id_eligibility_criteria)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_10BA42696DE44026F79B4C9A4C70DEF4 ON credit_guaranty_program_choice_option (description, id_eligibility_criteria, id_program)');
        $this->addSql('DROP INDEX IDX_E17147BBB5700468 ON credit_guaranty_program_eligibility');
        $this->addSql('DROP INDEX UNIQ_E17147BBB57004684C70DEF4 ON credit_guaranty_program_eligibility');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility CHANGE id_field id_eligibility_criteria INT NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility ADD CONSTRAINT FK_E17147BBF79B4C9A FOREIGN KEY (id_eligibility_criteria) REFERENCES credit_guaranty_eligibility_criteria (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_E17147BBF79B4C9A ON credit_guaranty_program_eligibility (id_eligibility_criteria)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E17147BBF79B4C9A4C70DEF4 ON credit_guaranty_program_eligibility (id_eligibility_criteria, id_program)');
    }
}
