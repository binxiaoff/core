<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191119112535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC47549F7C31B40');
        $this->addSql('DROP INDEX UNIQ_7FC47549F7C31B40 ON project_participation');
        $this->addSql('ALTER TABLE project_participation DROP project_participation_fee_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participation ADD project_participation_fee_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC47549F7C31B40 FOREIGN KEY (project_participation_fee_id) REFERENCES project_participation_fee (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7FC47549F7C31B40 ON project_participation (project_participation_fee_id)');
    }
}
