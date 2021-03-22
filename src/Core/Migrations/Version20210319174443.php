<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210317224954 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-3262 Create credit_guaranty_program_eligibility_condition table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE credit_guaranty_program_eligibility_condition (id INT AUTO_INCREMENT NOT NULL, id_program_eligibility_configuration INT NOT NULL, id_left_operand_field INT NOT NULL, id_right_operand_field INT DEFAULT NULL, operation VARCHAR(10) NOT NULL, value_type VARCHAR(20) NOT NULL, value NUMERIC(15, 2) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_F9820BF3B5B48B91 (public_id), INDEX IDX_F9820BF333C0C139 (id_program_eligibility_configuration), INDEX IDX_F9820BF34056F542 (id_left_operand_field), INDEX IDX_F9820BF324B37D48 (id_right_operand_field), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition ADD CONSTRAINT FK_F9820BF333C0C139 FOREIGN KEY (id_program_eligibility_configuration) REFERENCES credit_guaranty_program_eligibility_configuration (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition ADD CONSTRAINT FK_F9820BF34056F542 FOREIGN KEY (id_left_operand_field) REFERENCES credit_guaranty_field (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition ADD CONSTRAINT FK_F9820BF324B37D48 FOREIGN KEY (id_right_operand_field) REFERENCES credit_guaranty_field (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE credit_guaranty_program_eligibility_condition');
    }
}
