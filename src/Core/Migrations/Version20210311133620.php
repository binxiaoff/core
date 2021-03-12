<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210311133620 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-3255 : create table credit_guaranty_program_borrower_type_allocation';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE credit_guaranty_program_borrower_type_allocation (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, id_program_choice_option INT NOT NULL, max_allocation_rate NUMERIC(4, 4) DEFAULT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_5B4CC439B5B48B91 (public_id), INDEX IDX_5B4CC4394C70DEF4 (id_program), INDEX IDX_5B4CC439CB0F0BCB (id_program_choice_option), UNIQUE INDEX UNIQ_5B4CC4394C70DEF4CB0F0BCB (id_program, id_program_choice_option), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation ADD CONSTRAINT FK_5B4CC4394C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation ADD CONSTRAINT FK_5B4CC439CB0F0BCB FOREIGN KEY (id_program_choice_option) REFERENCES credit_guaranty_program_choice_option (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE credit_guaranty_program_borrower_type_allocation');
    }
}
