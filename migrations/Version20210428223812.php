<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210428223812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3749 Create ParticipationPool entity';
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

        $this->addSql('CREATE TABLE agency_participation_pool (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, syndication_type VARCHAR(30) DEFAULT NULL, participation_type VARCHAR(30) DEFAULT NULL, risk_type VARCHAR(30) DEFAULT NULL, secondary TINYINT(1) NOT NULL, public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_9D542F1FB5B48B91 (public_id), INDEX IDX_9D542F1FF12E799E (id_project), UNIQUE INDEX uniq_participant_pool_project_secondary (id_project, secondary), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO agency_participation_pool SELECT NULL, id, principal_participation_type, principal_participation_type, principal_risk_type, 0, {$uuid} FROM agency_project");
        $this->addSql("INSERT INTO agency_participation_pool SELECT NULL, id, secondary_participation_type, secondary_participation_type, secondary_risk_type, 1, {$uuid} FROM agency_project");
        $this->addSql('ALTER TABLE agency_participation_pool ADD CONSTRAINT FK_9D542F1FF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id)');
        $this->addSql('ALTER TABLE agency_participation DROP FOREIGN KEY FK_E0ED689EF12E799E');
        $this->addSql('DROP INDEX IDX_E0ED689EF12E799E ON agency_participation');
        $this->addSql('DROP INDEX UNIQ_E0ED689EF12E799ECF8DA6E6 ON agency_participation');
        $this->addSql('ALTER TABLE agency_participation ADD id_participation_pool INT NOT NULL');
        $this->addSql('UPDATE agency_participation ap INNER JOIN agency_participation_pool app ON ap.id_project = app.id_project AND ap.secondary = app.secondary SET ap.id_participation_pool = app.id');
        $this->addSql('ALTER TABLE agency_participation DROP secondary, DROP id_project');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689EF0C7A460 FOREIGN KEY (id_participation_pool) REFERENCES agency_participation_pool (id)');
        $this->addSql('CREATE INDEX IDX_E0ED689EF0C7A460 ON agency_participation (id_participation_pool)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E0ED689EF0C7A460CF8DA6E6 ON agency_participation (id_participation_pool, id_participant)');
        $this->addSql('ALTER TABLE agency_project DROP principal_syndication_type, DROP principal_participation_type, DROP principal_risk_type, DROP secondary_syndication_type, DROP secondary_participation_type, DROP secondary_risk_type');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_participation DROP FOREIGN KEY FK_E0ED689EF0C7A460');
        $this->addSql('DROP INDEX IDX_E0ED689EF0C7A460 ON agency_participation');
        $this->addSql('DROP INDEX UNIQ_E0ED689EF0C7A460CF8DA6E6 ON agency_participation');
        $this->addSql('ALTER TABLE agency_participation ADD id_project INT NOT NULL, ADD secondary TINYINT(1) NOT NULL');
        $this->addSql('UPDATE agency_participation ap INNER JOIN agency_participation_pool app ON ap.id_participation_pool = app.id SET ap.secondary = app.secondary, ap.id_project = app.id_project WHERE TRUE');
        $this->addSql('ALTER TABLE agency_participation DROP id_participation_pool');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689EF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_E0ED689EF12E799E ON agency_participation (id_project)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E0ED689EF12E799ECF8DA6E6 ON agency_participation (id_project, id_participant)');
        $this->addSql('ALTER TABLE agency_project ADD principal_syndication_type VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD principal_participation_type VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD principal_risk_type VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD secondary_syndication_type VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD secondary_participation_type VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD secondary_risk_type VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('UPDATE agency_project ap INNER JOIN agency_participation_pool app ON ap.id = app.id_project SET ap.principal_syndication_type = app.syndication_type, ap.principal_participation_type = app.participation_type, ap.principal_risk_type = app.risk_type WHERE app.secondary = 0');
        $this->addSql('UPDATE agency_project ap INNER JOIN agency_participation_pool app ON ap.id = app.id_project SET ap.secondary_syndication_type = app.syndication_type, ap.secondary_participation_type = app.participation_type, ap.secondary_risk_type = app.risk_type WHERE app.secondary = 1');
        $this->addSql('DROP TABLE agency_participation_pool');
    }
}
