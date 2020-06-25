<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200625132620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participation_tranche DROP FOREIGN KEY FK_6B56B4CBAE73E249');
        $this->addSql('ALTER TABLE project_participation_tranche ADD CONSTRAINT FK_6B56B4CBAE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_participation_status DROP FOREIGN KEY FK_2786D096AE73E249');
        $this->addSql('ALTER TABLE project_participation_status ADD CONSTRAINT FK_2786D096AE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_message DROP FOREIGN KEY FK_20A33C1A157D332A');
        $this->addSql('ALTER TABLE project_message ADD CONSTRAINT FK_20A33C1A157D332A FOREIGN KEY (id_participation) REFERENCES project_participation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC4754941AF0274');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC4754941AF0274 FOREIGN KEY (id_current_status) REFERENCES project_participation_status (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC4754941AF0274');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC4754941AF0274 FOREIGN KEY (id_current_status) REFERENCES project_participation_status (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_message DROP FOREIGN KEY FK_20A33C1A157D332A');
        $this->addSql('ALTER TABLE project_message ADD CONSTRAINT FK_20A33C1A157D332A FOREIGN KEY (id_participation) REFERENCES project_participation (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation_status DROP FOREIGN KEY FK_2786D096AE73E249');
        $this->addSql('ALTER TABLE project_participation_status ADD CONSTRAINT FK_2786D096AE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation_tranche DROP FOREIGN KEY FK_6B56B4CBAE73E249');
        $this->addSql('ALTER TABLE project_participation_tranche ADD CONSTRAINT FK_6B56B4CBAE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
