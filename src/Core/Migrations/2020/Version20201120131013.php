<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201120131013 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'CALS-2846 Add new data model for V2 messaging';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE core_message (id INT AUTO_INCREMENT NOT NULL, id_message_thread INT NOT NULL, id_sender INT NOT NULL, body MEDIUMTEXT NOT NULL, public_id VARCHAR(36) NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_B6BD307FB5B48B91 (public_id), INDEX IDX_B6BD307F3B616C8A (id_message_thread), INDEX IDX_B6BD307F7937FF22 (id_sender), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE core_message_file (id INT AUTO_INCREMENT NOT NULL, id_file INT NOT NULL, id_message INT NOT NULL, public_id VARCHAR(36) NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_250AADC9B5B48B91 (public_id), INDEX IDX_250AADC96820990F (id_message), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE core_message_status (id INT AUTO_INCREMENT NOT NULL, id_message INT NOT NULL, id_recipient INT NOT NULL, status SMALLINT NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4C27F8136820990F (id_message), INDEX IDX_4C27F813E831476E (id_recipient), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE core_message_thread (id INT AUTO_INCREMENT NOT NULL, public_id VARCHAR(36) NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_607D18CB5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE core_message ADD CONSTRAINT FK_B6BD307F3B616C8A FOREIGN KEY (id_message_thread) REFERENCES core_message_thread (id)');
        $this->addSql('ALTER TABLE core_message ADD CONSTRAINT FK_B6BD307F7937FF22 FOREIGN KEY (id_sender) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE core_message_file ADD CONSTRAINT FK_250AADC97BF2A12 FOREIGN KEY (id_file) REFERENCES file (id)');
        $this->addSql('ALTER TABLE core_message_file ADD CONSTRAINT FK_250AADC96820990F FOREIGN KEY (id_message) REFERENCES core_message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE core_message_status ADD CONSTRAINT FK_4C27F8136820990F FOREIGN KEY (id_message) REFERENCES core_message (id)');
        $this->addSql('ALTER TABLE core_message_status ADD CONSTRAINT FK_4C27F813E831476E FOREIGN KEY (id_recipient) REFERENCES staff (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_message_status DROP FOREIGN KEY FK_4C27F813E831476E');
        $this->addSql('ALTER TABLE core_message_status DROP FOREIGN KEY FK_4C27F8136820990F');
        $this->addSql('ALTER TABLE core_message_file DROP FOREIGN KEY FK_250AADC96820990F');
        $this->addSql('ALTER TABLE core_message_file DROP FOREIGN KEY FK_250AADC97BF2A12');
        $this->addSql('ALTER TABLE core_message DROP FOREIGN KEY FK_B6BD307F3B616C8A');
        $this->addSql('ALTER TABLE core_message DROP FOREIGN KEY FK_B6BD307F7937FF22');
        $this->addSql('DROP TABLE core_message_thread');
        $this->addSql('DROP TABLE core_message_status');
        $this->addSql('DROP TABLE core_message_file');
        $this->addSql('DROP TABLE core_message');
    }
}

