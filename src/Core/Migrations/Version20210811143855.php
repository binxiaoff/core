<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210811143855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Arrangement] CALS-4362 Add ON DELETE CASCADE to MessageThread::projectParticipation foreign key';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_message_thread DROP FOREIGN KEY FK_FD5B3803AE73E249');
        $this->addSql('ALTER TABLE core_message_thread ADD CONSTRAINT FK_FD5B3803AE73E249 FOREIGN KEY (id_project_participation) REFERENCES syndication_project_participation (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_message_thread DROP FOREIGN KEY FK_FD5B3803AE73E249');
        $this->addSql('ALTER TABLE core_message_thread ADD CONSTRAINT FK_FD5B3803AE73E249 FOREIGN KEY (id_project_participation) REFERENCES syndication_project_participation (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
