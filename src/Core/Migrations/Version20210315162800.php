<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210315162800 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-3261 add cascade deleting on id_program_choice_option';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_configuration DROP FOREIGN KEY FK_F485534DCB0F0BCB');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_configuration ADD CONSTRAINT FK_F485534DCB0F0BCB FOREIGN KEY (id_program_choice_option) REFERENCES credit_guaranty_program_choice_option (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_configuration DROP FOREIGN KEY FK_F485534DCB0F0BCB');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_configuration ADD CONSTRAINT FK_F485534DCB0F0BCB FOREIGN KEY (id_program_choice_option) REFERENCES credit_guaranty_program_choice_option (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
