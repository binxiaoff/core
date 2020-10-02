<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200618220652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CQLS-1535 Fix typo on column name';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participation_status DROP FOREIGN KEY FK_2786D0961D7F40EA');
        $this->addSql('DROP INDEX IDX_2786D0961D7F40EA ON project_participation_status');
        $this->addSql('DROP INDEX IDX_2786D0967B00651C1D7F40EA ON project_participation_status');
        $this->addSql('ALTER TABLE project_participation_status CHANGE id_project_parcitipation id_project_participation INT NOT NULL');
        $this->addSql('ALTER TABLE project_participation_status ADD CONSTRAINT FK_2786D096AE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id)');
        $this->addSql('CREATE INDEX IDX_2786D096AE73E249 ON project_participation_status (id_project_participation)');
        $this->addSql('CREATE INDEX IDX_2786D0967B00651CAE73E249 ON project_participation_status (status, id_project_participation)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participation_status DROP FOREIGN KEY FK_2786D096AE73E249');
        $this->addSql('DROP INDEX IDX_2786D096AE73E249 ON project_participation_status');
        $this->addSql('DROP INDEX IDX_2786D0967B00651CAE73E249 ON project_participation_status');
        $this->addSql('ALTER TABLE project_participation_status CHANGE id_project_participation id_project_parcitipation INT NOT NULL');
        $this->addSql('ALTER TABLE project_participation_status ADD CONSTRAINT FK_2786D0961D7F40EA FOREIGN KEY (id_project_parcitipation) REFERENCES project_participation (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_2786D0961D7F40EA ON project_participation_status (id_project_parcitipation)');
        $this->addSql('CREATE INDEX IDX_2786D0967B00651C1D7F40EA ON project_participation_status (status, id_project_parcitipation)');
    }
}
