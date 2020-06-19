<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200608134023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1537 Archive participation contact';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_participation_contact ADD archived DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE project_participation_contact ADD archived_by INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB351B07D6D FOREIGN KEY (archived_by) REFERENCES staff (id)');
        $this->addSql('CREATE INDEX IDX_41530AB351B07D6D ON project_participation_contact (archived_by)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_participation_contact DROP archived');
        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB351B07D6D');
        $this->addSql('DROP INDEX IDX_41530AB351B07D6D ON project_participation_contact');
        $this->addSql('ALTER TABLE project_participation_contact DROP archived_by');
    }
}
