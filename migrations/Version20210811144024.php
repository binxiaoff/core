<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210811144024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Arrangement] CALS-4362 Add ON DELETE CASCADE to Message::messageThread foreign key';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_message DROP FOREIGN KEY FK_A4AA854C3B616C8A');
        $this->addSql('ALTER TABLE core_message ADD CONSTRAINT FK_A4AA854C3B616C8A FOREIGN KEY (id_message_thread) REFERENCES core_message_thread (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_message DROP FOREIGN KEY FK_A4AA854C3B616C8A');
        $this->addSql('ALTER TABLE core_message ADD CONSTRAINT FK_A4AA854C3B616C8A FOREIGN KEY (id_message_thread) REFERENCES core_message_thread (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
