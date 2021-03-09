<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210305143543 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-3256 : Add credit_guaranty_program_grade_allocation table and add credit_guaranty_program.rating_type filed';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE credit_guaranty_program_grade_allocation (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, grade VARCHAR(10) NOT NULL, max_allocation_rate NUMERIC(4, 4) DEFAULT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_20B3F09AB5B48B91 (public_id), INDEX IDX_20B3F09A4C70DEF4 (id_program), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_program_grade_allocation ADD CONSTRAINT FK_20B3F09A4C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program ADD rating_type VARCHAR(60) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE credit_guaranty_program_grade_allocation');
        $this->addSql('ALTER TABLE credit_guaranty_program DROP rating_type');
    }
}
