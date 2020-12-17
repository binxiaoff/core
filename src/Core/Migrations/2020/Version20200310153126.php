<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200310153126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1282 Update sendat and toSendTo type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mail_queue CHANGE to_send_at to_send_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE sent_at sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mail_queue CHANGE to_send_at to_send_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE sent_at sent_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }
}
