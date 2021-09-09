<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210516212223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-3083 Finalize Agent and AgentMember';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent DROP FOREIGN KEY FK_284713B3C80EDDAD');
        $this->addSql('DROP INDEX IDX_284713B3C80EDDAD ON agency_agent');
        $this->addSql('ALTER TABLE agency_agent CHANGE id_agent id_company INT NOT NULL');
        $this->addSql('ALTER TABLE agency_agent ADD CONSTRAINT FK_284713B39122A03F FOREIGN KEY (id_company) REFERENCES core_company (id)');
        $this->addSql('CREATE INDEX IDX_284713B39122A03F ON agency_agent (id_company)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent DROP FOREIGN KEY FK_284713B39122A03F');
        $this->addSql('DROP INDEX IDX_284713B39122A03F ON agency_agent');
        $this->addSql('ALTER TABLE agency_agent CHANGE id_company id_agent INT NOT NULL');
        $this->addSql('ALTER TABLE agency_agent ADD CONSTRAINT FK_284713B3C80EDDAD FOREIGN KEY (id_agent) REFERENCES core_company (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_284713B3C80EDDAD ON agency_agent (id_agent)');
    }
}
