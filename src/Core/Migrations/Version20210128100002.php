<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210128100002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace marketSegment with tag';
    }

    public function up(Schema $schema): void
    {
        $uuid = "LOWER(
            CONCAT(
                HEX(RANDOM_BYTES(4)), '-',
                HEX(RANDOM_BYTES(2)), '-', 
                '4', SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3), '-', 
                CONCAT(HEX(FLOOR(ASCII(RANDOM_BYTES(1)) / 64)+8), SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3)), '-',
                HEX(RANDOM_BYTES(6))
            )
        )";
        $this->addSql('ALTER TABLE core_staff_market_segment DROP FOREIGN KEY FK_A055377AB5D73EB1');
        $this->addSql('ALTER TABLE syndication_project DROP FOREIGN KEY FK_7E9E0E6F2C71A0E3');
        $this->addSql('CREATE TABLE core_company_group_tag (id INT AUTO_INCREMENT NOT NULL, id_company_group INT NOT NULL, code VARCHAR(255) NOT NULL, public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_B408DC36B5B48B91 (public_id), INDEX IDX_B408DC36941937C5 (id_company_group), UNIQUE INDEX uniq_companyGroup_code (code, id_company_group), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE core_company_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_A2940CFF5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE staff_company_group_tag (staff_id INT NOT NULL, company_group_tag_id INT NOT NULL, INDEX IDX_CA55D2FD4D57CD (staff_id), INDEX IDX_CA55D2F9799710F (company_group_tag_id), PRIMARY KEY(staff_id, company_group_tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE team_company_group_tag (team_id INT NOT NULL, company_group_tag_id INT NOT NULL, INDEX IDX_FFDE5B56296CD8AE (team_id), INDEX IDX_FFDE5B569799710F (company_group_tag_id), PRIMARY KEY(team_id, company_group_tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE core_company ADD id_company_group INT DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_project ADD id_company_group_tag INT DEFAULT NULL');
        $this->addSql('INSERT INTO core_company_group SELECT DISTINCT NULL, group_name FROM core_company WHERE group_name IS NOT NULL');
        $this->addSql('UPDATE core_company INNER JOIN core_company_group ON core_company.group_name = core_company_group.name SET core_company.id_company_group = core_company_group.id WHERE core_company.group_name IS NOT NULL');
        $this->addSql(<<<SQL
            INSERT INTO core_company_group_tag 
            SELECT NULL, (SELECT id FROM core_company_group WHERE core_company_group.name = 'Crédit Agricole'), label, {$uuid} 
            FROM core_market_segment
            SQL
);
        $this->addSql(<<<'SQL'
            INSERT INTO staff_company_group_tag
            SELECT cs.id, ccgt.id 
            FROM core_staff cs
            INNER JOIN core_staff_market_segment sms on sms.staff_id = cs.id 
            INNER JOIN core_market_segment cm on sms.market_segment_id = cm.id
            INNER JOIN core_company_group_tag ccgt ON cm.label = ccgt.code
            SQL
);
        $this->addSql(<<<'SQL'
            INSERT INTO team_company_group_tag
            SELECT ct.id, cctg.id
            FROM core_company cc
            INNER JOIN core_team ct on cc.id_root_team = ct.id
            INNER JOIN core_company_group_tag cctg ON cctg.id_company_group = cc.id_company_group
            WHERE cc.id_company_group IS NOT NULL
            SQL
);
        $this->addSql('ALTER TABLE core_company_group_tag ADD CONSTRAINT FK_B408DC36941937C5 FOREIGN KEY (id_company_group) REFERENCES core_company_group (id)');
        $this->addSql('ALTER TABLE staff_company_group_tag ADD CONSTRAINT FK_CA55D2FD4D57CD FOREIGN KEY (staff_id) REFERENCES core_staff (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE staff_company_group_tag ADD CONSTRAINT FK_CA55D2F9799710F FOREIGN KEY (company_group_tag_id) REFERENCES core_company_group_tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_company_group_tag ADD CONSTRAINT FK_FFDE5B56296CD8AE FOREIGN KEY (team_id) REFERENCES core_team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_company_group_tag ADD CONSTRAINT FK_FFDE5B569799710F FOREIGN KEY (company_group_tag_id) REFERENCES core_company_group_tag (id) ON DELETE CASCADE');
        $this->addSql(<<<'SQL'
            UPDATE syndication_project sp 
            INNER JOIN core_market_segment cms on sp.id_market_segment = cms.id 
            CROSS JOIN core_company_group_tag ccgt SET sp.id_company_group_tag = ccgt.id 
            WHERE cms.label = ccgt.code
            SQL
);
        $this->addSql('DROP TABLE core_staff_market_segment');
        $this->addSql('DROP TABLE core_market_segment');
        $this->addSql('ALTER TABLE core_company DROP group_name');
        $this->addSql('ALTER TABLE core_company ADD CONSTRAINT FK_5DA8BC7C941937C5 FOREIGN KEY (id_company_group) REFERENCES core_company_group (id)');
        $this->addSql('CREATE INDEX IDX_5DA8BC7C941937C5 ON core_company (id_company_group)');
        $this->addSql('ALTER TABLE core_staff_log DROP previous_market_segment');
        $this->addSql('DROP INDEX IDX_7E9E0E6F2C71A0E3 ON syndication_project');
        $this->addSql('ALTER TABLE syndication_project DROP id_market_segment');
        $this->addSql('ALTER TABLE syndication_project ADD CONSTRAINT FK_7E9E0E6F4237BD1D FOREIGN KEY (id_company_group_tag) REFERENCES core_company_group_tag (id)');
        $this->addSql('CREATE INDEX IDX_7E9E0E6F4237BD1D ON syndication_project (id_company_group_tag)');

        $this->addSql('RENAME TABLE staff_company_group_tag TO core_staff_company_group_tag');
        $this->addSql('RENAME TABLE team_company_group_tag TO core_team_company_group_tag');

        $this->addSql('ALTER TABLE core_staff_company_group_tag RENAME INDEX idx_ca55d2fd4d57cd TO IDX_B2F6C6E2D4D57CD');
        $this->addSql('ALTER TABLE core_staff_company_group_tag RENAME INDEX idx_ca55d2f9799710f TO IDX_B2F6C6E29799710F');
        $this->addSql('ALTER TABLE core_team_company_group_tag RENAME INDEX idx_ffde5b56296cd8ae TO IDX_A7558624296CD8AE');
        $this->addSql('ALTER TABLE core_team_company_group_tag RENAME INDEX idx_ffde5b569799710f TO IDX_A75586249799710F');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_staff_company_group_tag RENAME INDEX idx_b2f6c6e29799710f TO IDX_CA55D2F9799710F');
        $this->addSql('ALTER TABLE core_staff_company_group_tag RENAME INDEX idx_b2f6c6e2d4d57cd TO IDX_CA55D2FD4D57CD');
        $this->addSql('ALTER TABLE core_team_company_group_tag RENAME INDEX idx_a7558624296cd8ae TO IDX_FFDE5B56296CD8AE');
        $this->addSql('ALTER TABLE core_team_company_group_tag RENAME INDEX idx_a75586249799710f TO IDX_FFDE5B569799710F');

        $this->addSql('RENAME TABLE core_staff_company_group_tag TO staff_company_group_tag');
        $this->addSql('RENAME TABLE core_team_company_group_tag TO team_company_group_tag');

        $this->addSql('ALTER TABLE core_company ADD group_name VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql(<<<'SQL'
            UPDATE core_company
            INNER JOIN core_company_group ON core_company.id_company_group = core_company_group.id 
            SET core_company.group_name = core_company_group.name WHERE core_company.id_company_group IS NOT NULL
            SQL
        );
        $this->addSql('CREATE TABLE core_market_segment (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql(<<<'SQL'
            INSERT INTO core_market_segment 
            SELECT NULL, ccgt.code 
            FROM core_company_group_tag ccgt 
            INNER JOIN core_company_group ccg ON ccg.id = ccgt.id_company_group
            WHERE ccg.name = 'Crédit Agricole'
            SQL
        );
        $this->addSql('CREATE TABLE core_staff_market_segment (staff_id INT NOT NULL, market_segment_id INT NOT NULL, INDEX IDX_A055377AB5D73EB1 (market_segment_id), INDEX IDX_A055377AD4D57CD (staff_id), PRIMARY KEY(staff_id, market_segment_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql(<<<'SQL'
            INSERT INTO core_staff_market_segment 
            SELECT scgt.staff_id, cmm.id
            FROM staff_company_group_tag scgt
            INNER JOIN core_company_group_tag ccgt ON scgt.company_group_tag_id = ccgt.id
            INNER JOIN core_market_segment cmm ON ccgt.code = cmm.label
            SQL
        );
        $this->addSql('ALTER TABLE syndication_project ADD id_market_segment INT NOT NULL');
        $this->addSql(<<<'SQL'
            UPDATE syndication_project sp
            INNER JOIN core_company_group_tag ccgt ON sp.id_company_group_tag = ccgt.id
            INNER JOIN core_market_segment cms ON ccgt.code = cms.label
            SET sp.id_market_segment = cms.id
            WHERE TRUE
            SQL
        );
        $this->addSql('ALTER TABLE core_company DROP FOREIGN KEY FK_5DA8BC7C941937C5');
        $this->addSql('ALTER TABLE core_company_group_tag DROP FOREIGN KEY FK_B408DC36941937C5');
        $this->addSql('ALTER TABLE staff_company_group_tag DROP FOREIGN KEY FK_CA55D2F9799710F');
        $this->addSql('ALTER TABLE team_company_group_tag DROP FOREIGN KEY FK_FFDE5B569799710F');
        $this->addSql('ALTER TABLE syndication_project DROP FOREIGN KEY FK_7E9E0E6F4237BD1D');
        $this->addSql('ALTER TABLE core_staff_market_segment ADD CONSTRAINT FK_A055377AB5D73EB1 FOREIGN KEY (market_segment_id) REFERENCES core_market_segment (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE core_staff_market_segment ADD CONSTRAINT FK_523D18F2D4D57CD FOREIGN KEY (staff_id) REFERENCES core_staff (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP INDEX IDX_5DA8BC7C941937C5 ON core_company');
        $this->addSql('ALTER TABLE core_company DROP id_company_group');
        $this->addSql('ALTER TABLE core_staff_log ADD previous_market_segment JSON NOT NULL');
        $this->addSql('DROP INDEX IDX_7E9E0E6F4237BD1D ON syndication_project');
        $this->addSql('ALTER TABLE syndication_project DROP id_company_group_tag');
        $this->addSql('ALTER TABLE syndication_project ADD CONSTRAINT FK_7E9E0E6F2C71A0E3 FOREIGN KEY (id_market_segment) REFERENCES core_market_segment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_7E9E0E6F2C71A0E3 ON syndication_project (id_market_segment)');
        $this->addSql('DROP TABLE core_company_group');
        $this->addSql('DROP TABLE core_company_group_tag');
        $this->addSql('DROP TABLE staff_company_group_tag');
        $this->addSql('DROP TABLE team_company_group_tag');
    }
}
