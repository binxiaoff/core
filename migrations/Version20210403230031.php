<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210403230031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace foreign key targeting Staff to targeting User for FileVersion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_file_version ADD id_company INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_file_version ADD CONSTRAINT FK_49CAD3209122A03F FOREIGN KEY (id_company) REFERENCES core_company (id)');
        $this->addSql('CREATE INDEX IDX_49CAD3209122A03F ON core_file_version (id_company)');
        $this->addSql(<<<'SQL'
                    UPDATE core_file_version cfv 
                    INNER JOIN core_staff cs ON cfv.added_by = cs.id
                    LEFT JOIN core_team_edge cte ON cte.id_descendent = cs.id_team
                    LEFT JOIN core_company cc ON cc.id_root_team = COALESCE(cte.id_ancestor, cs.id_team)
                    SET cfv.id_company = cc.id 
                    WHERE cc.id IS NOT NULL
            SQL
);
        $this->addSql('ALTER TABLE core_file_version DROP FOREIGN KEY FK_49CAD320699B6BAF');
        $this->addSql('UPDATE core_file_version cfv INNER JOIN core_staff cs ON cfv.added_by = cs.id SET cfv.added_by = cs.id_user WHERE TRUE');
        $this->addSql('ALTER TABLE core_file_version ADD CONSTRAINT FK_49CAD320699B6BAF FOREIGN KEY (added_by) REFERENCES core_user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_file_version DROP FOREIGN KEY FK_49CAD3209122A03F');
        $this->addSql('DROP INDEX IDX_49CAD3209122A03F ON core_file_version');
        $this->addSql('ALTER TABLE core_file_version DROP id_company');
        $this->addSql('ALTER TABLE core_file_version DROP FOREIGN KEY FK_49CAD320699B6BAF');
        $this->addSql('UPDATE core_file_version cfv SET cfv.added_by = (SELECT id FROM core_staff LIMIT 1) WHERE TRUE');
        $this->addSql('ALTER TABLE core_file_version ADD CONSTRAINT FK_49CAD320699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
