<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210224104707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-3075 Add shared drives';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project ADD id_agent_borrower_drive INT NOT NULL, ADD id_agent_principal_borrower_drive INT NOT NULL, ADD id_agent_secondary_borrower_drive INT NOT NULL');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFA678F821 FOREIGN KEY (id_agent_borrower_drive) REFERENCES core_drive (id)');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFAD3D811 FOREIGN KEY (id_agent_principal_borrower_drive) REFERENCES core_drive (id)');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFB5F15EDF FOREIGN KEY (id_agent_secondary_borrower_drive) REFERENCES core_drive (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_59B349BFA678F821 ON agency_project (id_agent_borrower_drive)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_59B349BFAD3D811 ON agency_project (id_agent_principal_borrower_drive)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_59B349BFB5F15EDF ON agency_project (id_agent_secondary_borrower_drive)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFA678F821');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFAD3D811');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFB5F15EDF');
        $this->addSql('DROP INDEX UNIQ_59B349BFA678F821 ON agency_project');
        $this->addSql('DROP INDEX UNIQ_59B349BFAD3D811 ON agency_project');
        $this->addSql('DROP INDEX UNIQ_59B349BFB5F15EDF ON agency_project');
        $this->addSql('ALTER TABLE agency_project DROP id_agent_borrower_drive, DROP id_agent_principal_borrower_drive, DROP id_agent_secondary_borrower_drive');
    }
}
