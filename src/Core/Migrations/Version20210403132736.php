<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210403132736 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'Replace association target of addedBy field in FileDownload from Staff to User';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE core_file_download ADD id_company INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_file_download ADD CONSTRAINT FK_41EFE7B29122A03F FOREIGN KEY (id_company) REFERENCES core_company (id)');
        $this->addSql('CREATE INDEX IDX_41EFE7B29122A03F ON core_file_download (id_company)');
        $this->addSql(<<<'SQL'
        UPDATE core_file_download cfd 
        INNER JOIN core_staff cs ON cfd.added_by = cs.id
        LEFT JOIN core_team_edge cte ON cte.id_descendent = cs.id_team
        LEFT JOIN core_company cc ON cc.id_root_team = COALESCE(cte.id_ancestor, cs.id_team)
        SET cfd.id_company = cc.id 
        WHERE cc.id IS NOT NULL
SQL
        );
        $this->addSql('ALTER TABLE core_file_download DROP FOREIGN KEY FK_41EFE7B2699B6BAF');
        $this->addSql('UPDATE core_file_download cfd INNER JOIN core_staff cs on cfd.added_by = cs.id SET cfd.added_by = cs.id_user WHERE TRUE');
        $this->addSql('ALTER TABLE core_file_download ADD CONSTRAINT FK_41EFE7B2699B6BAF FOREIGN KEY (added_by) REFERENCES core_user (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE core_file_download DROP FOREIGN KEY FK_41EFE7B29122A03F');
        $this->addSql('DROP INDEX IDX_41EFE7B29122A03F ON core_file_download');
        $this->addSql('ALTER TABLE core_file_download DROP id_company');
        $this->addSql('ALTER TABLE core_file_download DROP FOREIGN KEY FK_41EFE7B2699B6BAF');
        $this->addSql('UPDATE core_file_download cfd SET cfd.added_by = (SELECT id FROM unilend.core_staff LIMIT 1) WHERE TRUE');
        $this->addSql('ALTER TABLE core_file_download ADD CONSTRAINT FK_41EFE7B2699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
