<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201202131344 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2846 Update on V2 messaging model';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message_thread ADD id_project_participation INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message_thread ADD CONSTRAINT FK_607D18CAE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_607D18CAE73E249 ON message_thread (id_project_participation)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message_thread DROP FOREIGN KEY FK_607D18CAE73E249');
        $this->addSql('DROP INDEX UNIQ_607D18CAE73E249 ON message_thread');
        $this->addSql('ALTER TABLE message_thread DROP id_project_participation');
    }
}
