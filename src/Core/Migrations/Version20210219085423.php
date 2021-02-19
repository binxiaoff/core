<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210219085423 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return "CALS-3254 : add program's CASA contacts";
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE credit_guaranty_program_contact (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, working_scope VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, phone VARCHAR(35) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_FB6A0C29B5B48B91 (public_id), INDEX IDX_FB6A0C294C70DEF4 (id_program), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_program_contact ADD CONSTRAINT FK_FB6A0C294C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE credit_guaranty_program_contact');
    }
}
