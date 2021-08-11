<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210811144311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Arrangement] CALS-4362 Add ON DELETE CASCADE to MessageStatus::message foreign key';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_message_status DROP FOREIGN KEY FK_B77B119C6820990F');
        $this->addSql('ALTER TABLE core_message_status ADD CONSTRAINT FK_B77B119C6820990F FOREIGN KEY (id_message) REFERENCES core_message (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_message_status DROP FOREIGN KEY FK_B77B119C6820990F');
        $this->addSql('ALTER TABLE core_message_status ADD CONSTRAINT FK_B77B119C6820990F FOREIGN KEY (id_message) REFERENCES core_message (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
