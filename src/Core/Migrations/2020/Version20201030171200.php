<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201030171200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2687 Add DatabaseSpool table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mail_queue (id_queue INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', scheduled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', recipients JSON NOT NULL, serialized LONGTEXT NOT NULL, hash VARCHAR(255) NOT NULL, mailjet_template_id INT DEFAULT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX status (status), INDEX idx_mail_queue_sent_at (sent_at), PRIMARY KEY(id_queue)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE mail_queue');
    }
}
