<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210315102722 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation DROP FOREIGN KEY FK_5B4CC439CB0F0BCB');
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation CHANGE max_allocation_rate max_allocation_rate NUMERIC(3, 2) NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation ADD CONSTRAINT FK_5B4CC439CB0F0BCB FOREIGN KEY (id_program_choice_option) REFERENCES credit_guaranty_program_choice_option (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE credit_guaranty_program_grade_allocation CHANGE max_allocation_rate max_allocation_rate NUMERIC(3, 2) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation DROP FOREIGN KEY FK_5B4CC439CB0F0BCB');
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation CHANGE max_allocation_rate max_allocation_rate NUMERIC(4, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation ADD CONSTRAINT FK_5B4CC439CB0F0BCB FOREIGN KEY (id_program_choice_option) REFERENCES credit_guaranty_program_choice_option (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE credit_guaranty_program_grade_allocation CHANGE max_allocation_rate max_allocation_rate NUMERIC(4, 4) DEFAULT NULL');
    }
}
