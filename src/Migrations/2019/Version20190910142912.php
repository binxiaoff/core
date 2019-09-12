<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190910142912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS 289 Add client attribute in ProjectParticipant entity';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participant ADD id_client INT AFTER id_company');
        $this->addSql('ALTER TABLE project_participant ADD CONSTRAINT FK_1F509CEAE173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client)');
        $this->addSql('CREATE INDEX IDX_1F509CEAE173B1B8 ON project_participant (id_client)');
        $this->addSql('DROP INDEX UNIQ_1F509CEAF12E799E9122A03F ON project_participant');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1F509CEAF12E799E9122A03FE173B1B8 ON project_participant (id_project, id_company, id_client)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participant DROP FOREIGN KEY FK_1F509CEAE173B1B8');
        $this->addSql('DROP INDEX IDX_1F509CEAE173B1B8 ON project_participant');
        $this->addSql('ALTER TABLE project_participant DROP id_client');
        $this->addSql('DROP INDEX UNIQ_1F509CEAF12E799E9122A03FE173B1B8 ON project_participant');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1F509CEAF12E799E9122A03F ON project_participant (id_project, id_company)');
    }
}
