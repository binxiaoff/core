<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190801075501 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('ALTER TABLE tree CHANGE id_tree id_tree INT AUTO_INCREMENT NOT NULL');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');

        $this->addSql('ALTER TABLE users CHANGE password_edited password_edited DATETIME DEFAULT NULL, CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE lastlogin lastlogin DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('ALTER TABLE tree CHANGE id_tree id_tree INT NOT NULL');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');

        $this->addSql('ALTER TABLE users CHANGE lastlogin lastlogin DATETIME NOT NULL, CHANGE updated updated DATETIME NOT NULL, CHANGE added added DATETIME NOT NULL, CHANGE password_edited password_edited DATETIME NOT NULL');
    }
}
