<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210128100001 extends AbstractMigration
{
    // Previous roles
    public const DUTY_STAFF_MANAGER    = 'DUTY_STAFF_MANAGER';
    public const DUTY_STAFF_ADMIN      = 'DUTY_STAFF_ADMIN';
    public const DUTY_STAFF_OPERATOR   = 'DUTY_STAFF_OPERATOR';
    public const DUTY_STAFF_AUDITOR    = 'DUTY_STAFF_AUDITOR';
    public const DUTY_STAFF_ACCOUNTANT = 'DUTY_STAFF_ACCOUNTANT';
    public const DUTY_STAFF_SIGNATORY  = 'DUTY_STAFF_SIGNATORY';

    public function getDescription(): string
    {
        return 'Update schema for new habilitations';
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

        $this->addSql('CREATE TABLE company_admin (id INT AUTO_INCREMENT NOT NULL, id_company INT DEFAULT NULL, id_user INT DEFAULT NULL, public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_CFFFAF13B5B48B91 (public_id), INDEX IDX_CFFFAF139122A03F (id_company), INDEX IDX_CFFFAF136B3CA4B (id_user), UNIQUE INDEX uniq_companyAdmin_companu_user (id_company, id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO company_admin SELECT NULL, id_company, id_user, {$uuid} FROM core_staff WHERE roles LIKE '%" . self::DUTY_STAFF_ADMIN . "%'");
        $this->addSql('CREATE TABLE nda_signature (id INT AUTO_INCREMENT NOT NULL, id_project_participation INT NOT NULL, id_file_version INT DEFAULT NULL, added_by INT NOT NULL, term TEXT DEFAULT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_41F146C9B5B48B91 (public_id), INDEX IDX_41F146C9AE73E249 (id_project_participation), INDEX IDX_41F146C9C7BB1F8A (id_file_version), INDEX IDX_41F146C9699B6BAF (added_by), UNIQUE INDEX uniq_added_by_project_participation (added_by, id_project_participation), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO nda_signature SELECT NULL, id_project_participation, id_accepted_nda_version, id_staff, accepted_nda_term, nda_accepted, {$uuid} FROM syndication_project_participation_member WHERE id_accepted_nda_version IS NOT NULL");
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, id_parent INT DEFAULT NULL, name VARCHAR(255) NOT NULL, public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_C4E0A61FB5B48B91 (public_id), INDEX IDX_C4E0A61F1BB9D5A2 (id_parent), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO team SELECT NULL, NULL, id, {$uuid} FROM core_company");
        $this->addSql('ALTER TABLE company_admin ADD CONSTRAINT FK_CFFFAF139122A03F FOREIGN KEY (id_company) REFERENCES core_company (id)');
        $this->addSql('ALTER TABLE company_admin ADD CONSTRAINT FK_CFFFAF136B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id)');
        $this->addSql('ALTER TABLE nda_signature ADD CONSTRAINT FK_41F146C9AE73E249 FOREIGN KEY (id_project_participation) REFERENCES syndication_project_participation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nda_signature ADD CONSTRAINT FK_41F146C9C7BB1F8A FOREIGN KEY (id_file_version) REFERENCES core_file_version (id)');
        $this->addSql('ALTER TABLE nda_signature ADD CONSTRAINT FK_41F146C9699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id)');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F1BB9D5A2 FOREIGN KEY (id_parent) REFERENCES team (id)');
        $this->addSql('ALTER TABLE core_company ADD id_root_team INT NOT NULL');
        $this->addSql('UPDATE core_company SET id_root_team = (SELECT id FROM team WHERE name = core_company.id)');
        $this->addSql("UPDATE team SET name = 'root' WHERE TRUE");
        $this->addSql('ALTER TABLE core_company ADD CONSTRAINT FK_5DA8BC7C308A30F FOREIGN KEY (id_root_team) REFERENCES team (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5DA8BC7C308A30F ON core_company (id_root_team)');
        $this->addSql('ALTER TABLE core_staff DROP FOREIGN KEY FK_14EFD2729122A03F');
        $this->addSql('DROP INDEX IDX_14EFD2729122A03F ON core_staff');
        $this->addSql('DROP INDEX UNIQ_14EFD2726B3CA4B9122A03F ON core_staff');
        $this->addSql('ALTER TABLE core_staff ADD manager TINYINT(1) NOT NULL, ADD arrangement_project_creation_permission TINYINT(1) NOT NULL, ADD agency_project_creation_permission TINYINT(1) NOT NULL, ADD id_team INT NOT NULL');
        $this->addSql("UPDATE core_staff SET arrangement_project_creation_permission = 1 WHERE JSON_SEARCH(roles, 'one','" . static::DUTY_STAFF_ADMIN . "') IS NOT NULL");
        $this->addSql("UPDATE core_staff SET arrangement_project_creation_permission = 1 WHERE JSON_SEARCH(roles, 'one','" . static::DUTY_STAFF_MANAGER . "') IS NOT NULL");
        $this->addSql("UPDATE core_staff SET arrangement_project_creation_permission = 1 WHERE JSON_SEARCH(roles, 'one','" . static::DUTY_STAFF_OPERATOR . "') IS NOT NULL");
        $this->addSql('UPDATE core_staff JOIN core_company cc on cc.id = core_staff.id_company SET id_team = cc.id_root_team');
        $this->addSql("UPDATE core_staff SET manager = 1 WHERE JSON_SEARCH(roles, 'one','" . static::DUTY_STAFF_ADMIN . "') IS NOT NULL");
        $this->addSql("UPDATE core_staff SET manager = 1 WHERE JSON_SEARCH(roles, 'one','" . static::DUTY_STAFF_MANAGER . "') IS NOT NULL");
        $this->addSql('ALTER TABLE core_staff DROP roles, DROP id_company');
        $this->addSql('ALTER TABLE core_staff ADD CONSTRAINT FK_14EFD2724FC0BA1D FOREIGN KEY (id_team) REFERENCES team (id)');
        $this->addSql('CREATE INDEX IDX_14EFD2724FC0BA1D ON core_staff (id_team)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_14EFD2726B3CA4B4FC0BA1D ON core_staff (id_user, id_team)');
        $this->addSql('ALTER TABLE core_staff_log DROP previous_roles');
        $this->addSql('ALTER TABLE syndication_project_participation_member DROP FOREIGN KEY FK_4CEF2D05EFC7EA74');
        $this->addSql('DROP INDEX IDX_4CEF2D05EFC7EA74 ON syndication_project_participation_member');
        $this->addSql('ALTER TABLE syndication_project_participation_member ADD permissions INT NOT NULL COMMENT \'(DC2Type:bitmask)\', DROP id_accepted_nda_version, DROP nda_accepted, DROP accepted_nda_term');
        $this->addSql('UPDATE syndication_project_participation_member SET permissions = 1 WHERE TRUE');
        $this->addSql('RENAME TABLE team TO core_team');
        $this->addSql('RENAME TABLE company_admin TO core_company_admin');
        $this->addSql('RENAME TABLE nda_signature TO syndication_nda_signature');
        $this->addSql('ALTER TABLE core_company_admin RENAME INDEX uniq_cfffaf13b5b48b91 TO UNIQ_475A454CB5B48B91');
        $this->addSql('ALTER TABLE core_company_admin RENAME INDEX idx_cfffaf139122a03f TO IDX_475A454C9122A03F');
        $this->addSql('ALTER TABLE core_company_admin RENAME INDEX idx_cfffaf136b3ca4b TO IDX_475A454C6B3CA4B');
        $this->addSql('ALTER TABLE core_team RENAME INDEX uniq_c4e0a61fb5b48b91 TO UNIQ_F605652AB5B48B91');
        $this->addSql('ALTER TABLE core_team RENAME INDEX idx_c4e0a61f1bb9d5a2 TO IDX_F605652A1BB9D5A2');
        $this->addSql('ALTER TABLE syndication_nda_signature RENAME INDEX uniq_41f146c9b5b48b91 TO UNIQ_9DFE73E8B5B48B91');
        $this->addSql('ALTER TABLE syndication_nda_signature RENAME INDEX idx_41f146c9ae73e249 TO IDX_9DFE73E8AE73E249');
        $this->addSql('ALTER TABLE syndication_nda_signature RENAME INDEX idx_41f146c9c7bb1f8a TO IDX_9DFE73E8C7BB1F8A');
        $this->addSql('ALTER TABLE syndication_nda_signature RENAME INDEX idx_41f146c9699b6baf TO IDX_9DFE73E8699B6BAF');
        $this->addSql('ALTER TABLE syndication_nda_signature CHANGE term term TEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE syndication_nda_signature CHANGE term term TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE core_company_admin RENAME INDEX idx_475a454c6b3ca4b TO IDX_CFFFAF136B3CA4B');
        $this->addSql('ALTER TABLE core_company_admin RENAME INDEX idx_475a454c9122a03f TO IDX_CFFFAF139122A03F');
        $this->addSql('ALTER TABLE core_company_admin RENAME INDEX uniq_475a454cb5b48b91 TO UNIQ_CFFFAF13B5B48B91');
        $this->addSql('ALTER TABLE core_team RENAME INDEX idx_f605652a1bb9d5a2 TO IDX_C4E0A61F1BB9D5A2');
        $this->addSql('ALTER TABLE core_team RENAME INDEX uniq_f605652ab5b48b91 TO UNIQ_C4E0A61FB5B48B91');
        $this->addSql('ALTER TABLE syndication_nda_signature RENAME INDEX idx_9dfe73e8699b6baf TO IDX_41F146C9699B6BAF');
        $this->addSql('ALTER TABLE syndication_nda_signature RENAME INDEX idx_9dfe73e8ae73e249 TO IDX_41F146C9AE73E249');
        $this->addSql('ALTER TABLE syndication_nda_signature RENAME INDEX idx_9dfe73e8c7bb1f8a TO IDX_41F146C9C7BB1F8A');
        $this->addSql('ALTER TABLE syndication_nda_signature RENAME INDEX uniq_9dfe73e8b5b48b91 TO UNIQ_41F146C9B5B48B91');

        $this->addSql('RENAME TABLE core_team TO team');
        $this->addSql('RENAME TABLE core_company_admin TO company_admin');
        $this->addSql('RENAME TABLE syndication_nda_signature TO nda_signature');

        $adminRole    = static::DUTY_STAFF_ADMIN;
        $managerRole  = static::DUTY_STAFF_MANAGER;
        $operatorRole = static::DUTY_STAFF_OPERATOR;

        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_staff ADD roles JSON NOT NULL');
        $this->addSql(<<<SQL
            UPDATE core_staff 
                INNER JOIN core_company ON core_company.id_root_team = core_staff.id_team
                INNER JOIN company_admin ON staff.id_user = company_admin.id_user AND company_admin.id_company = core_company.id
                SET roles = JSON_ARRAY_INSERT(IFNULL(roles,'[]'), '$[0]', '{$adminRole}')
                WHERE TRUE
            SQL);
        $this->addSql(<<<SQL
            UPDATE core_staff
                SET roles = JSON_ARRAY_INSERT(IFNULL(roles,'[]'), '$[0]', '{$managerRole}')
                WHERE core_staff.manager = 1
            SQL);
        $this->addSql(<<<SQL
            UPDATE core_staff
                SET roles = JSON_ARRAY_INSERT('[]', '$[0]', '{$operatorRole}')
                WHERE core_staff.roles IS NULL
            SQL);
        $this->addSql('ALTER TABLE core_staff ADD id_company INT NOT NULL');
        $this->addSql(<<<'SQL'
            WITH RECURSIVE tree AS (
                SELECT team.*, c.id as company
                FROM team
                INNER JOIN core_company c ON c.id_root_team = team.id
                UNION ALL
                SELECT team.*, tree.company
                FROM team
                INNER JOIN tree ON tree.id_parent = team.id
            )
            UPDATE core_staff
                INNER JOIN tree t ON core_staff.id_team = t.id
                SET id_company = tree.company
                WHERE TRUE
            SQL);
        $this->addSql('ALTER TABLE core_company DROP FOREIGN KEY FK_5DA8BC7C308A30F');
        $this->addSql('ALTER TABLE core_staff DROP FOREIGN KEY FK_14EFD2724FC0BA1D');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F1BB9D5A2');
        $this->addSql('DROP TABLE company_admin');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP INDEX UNIQ_5DA8BC7C308A30F ON core_company');
        $this->addSql('ALTER TABLE core_company DROP id_root_team');
        $this->addSql('DROP INDEX IDX_14EFD2724FC0BA1D ON core_staff');
        $this->addSql('DROP INDEX UNIQ_14EFD2726B3CA4B4FC0BA1D ON core_staff');
        $this->addSql('ALTER TABLE core_staff DROP manager, DROP arrangement_project_creation_permission, DROP agency_project_creation_permission, CHANGE id_team id_company INT NOT NULL');
        $this->addSql('ALTER TABLE core_staff ADD CONSTRAINT FK_14EFD2729122A03F FOREIGN KEY (id_company) REFERENCES core_company (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_14EFD2729122A03F ON core_staff (id_company)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_14EFD2726B3CA4B9122A03F ON core_staff (id_user, id_company)');
        $this->addSql('ALTER TABLE core_staff_log ADD previous_roles JSON NOT NULL');
        $this->addSql('ALTER TABLE syndication_project_participation_member ADD id_accepted_nda_version INT DEFAULT NULL, ADD nda_accepted DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD accepted_nda_term TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP permissions');
        $this->addSql('ALTER TABLE syndication_project_participation_member ADD CONSTRAINT FK_4CEF2D05EFC7EA74 FOREIGN KEY (id_accepted_nda_version) REFERENCES core_file_version (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql(<<<'SQL'
            UPDATE syndication_project_participation_member 
                INNER JOIN nda_signature ns on syndication_project_participation_member.id_staff = ns.added_by 
                       AND syndication_project_participation_member.id_project_participation = ns.id_project_participation
            SET accepted_nda_term = ns.term, ns.added = nda_accepted, ns.id_file_version = id_accepted_nda_version
            WHERE TRUE
            SQL
        );
        $this->addSql('CREATE INDEX IDX_4CEF2D05EFC7EA74 ON syndication_project_participation_member (id_accepted_nda_version)');
        $this->addSql('DROP TABLE nda_signature');
    }
}
