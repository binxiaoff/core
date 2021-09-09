<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210511103525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Add delete cascade for project foreign key in covenant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_covenant DROP FOREIGN KEY FK_E8F1E10CF12E799E');
        $this->addSql('ALTER TABLE agency_covenant CHANGE id_project id_project INT NOT NULL');
        $this->addSql('ALTER TABLE agency_covenant ADD CONSTRAINT FK_E8F1E10CF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_tranche DROP CONSTRAINT FK_1067C111F12E799E');
        $this->addSql('ALTER TABLE agency_tranche CHANGE id_project id_project INT NOT NULL');
        $this->addSql('ALTER TABLE agency_tranche ADD CONSTRAINT FK_1067C111F12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4F166D1F9C');
        $this->addSql('DROP INDEX IDX_C78A2C4F166D1F9C ON agency_borrower');
        $this->addSql('ALTER TABLE agency_borrower ADD id_project INT NOT NULL, DROP project_id');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4FF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_C78A2C4FF12E799E ON agency_borrower (id_project)');

        $this->addSql('ALTER TABLE agency_margin_impact DROP FOREIGN KEY FK_BE66DFA32384C64D');
        $this->addSql('ALTER TABLE agency_margin_impact CHANGE id_margin_rule id_margin_rule INT NOT NULL');
        $this->addSql('ALTER TABLE agency_margin_impact ADD CONSTRAINT FK_BE66DFA32384C64D FOREIGN KEY (id_margin_rule) REFERENCES agency_margin_rule (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_margin_rule DROP FOREIGN KEY FK_383340BA4306C62');
        $this->addSql('ALTER TABLE agency_margin_rule CHANGE id_covenant id_covenant INT NOT NULL');
        $this->addSql('ALTER TABLE agency_margin_rule ADD CONSTRAINT FK_383340BA4306C62 FOREIGN KEY (id_covenant) REFERENCES agency_covenant (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE agency_covenant_rule DROP FOREIGN KEY FK_926F7788A4306C62');
        $this->addSql('ALTER TABLE agency_covenant_rule CHANGE id_covenant id_covenant INT NOT NULL');
        $this->addSql('ALTER TABLE agency_covenant_rule ADD CONSTRAINT FK_926F7788A4306C62 FOREIGN KEY (id_covenant) REFERENCES agency_covenant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_covenant_rule DROP FOREIGN KEY FK_926F7788A4306C62');
        $this->addSql('ALTER TABLE agency_covenant_rule CHANGE id_covenant id_covenant INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_covenant_rule ADD CONSTRAINT FK_926F7788A4306C62 FOREIGN KEY (id_covenant) REFERENCES agency_covenant (id) ON UPDATE NO ACTION ON DELETE NO ACTION');

        $this->addSql('ALTER TABLE agency_margin_impact DROP FOREIGN KEY FK_BE66DFA32384C64D');
        $this->addSql('ALTER TABLE agency_margin_impact CHANGE id_margin_rule id_margin_rule INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_margin_impact ADD CONSTRAINT FK_BE66DFA32384C64D FOREIGN KEY (id_margin_rule) REFERENCES agency_margin_rule (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_margin_rule DROP FOREIGN KEY FK_383340BA4306C62');
        $this->addSql('ALTER TABLE agency_margin_rule CHANGE id_covenant id_covenant INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_margin_rule ADD CONSTRAINT FK_383340BA4306C62 FOREIGN KEY (id_covenant) REFERENCES agency_covenant (id) ON UPDATE NO ACTION ON DELETE NO ACTION');

        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4FF12E799E');
        $this->addSql('DROP INDEX IDX_C78A2C4FF12E799E ON agency_borrower');
        $this->addSql('ALTER TABLE agency_borrower ADD project_id INT DEFAULT NULL, DROP id_project');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4F166D1F9C FOREIGN KEY (project_id) REFERENCES agency_project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_C78A2C4F166D1F9C ON agency_borrower (project_id)');

        $this->addSql('ALTER TABLE agency_covenant DROP FOREIGN KEY FK_E8F1E10CF12E799E');
        $this->addSql('ALTER TABLE agency_covenant CHANGE id_project id_project INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_covenant ADD CONSTRAINT FK_E8F1E10CF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_tranche DROP CONSTRAINT FK_1067C111F12E799E');
        $this->addSql('ALTER TABLE agency_tranche CHANGE id_project id_project INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_tranche ADD CONSTRAINT FK_1067C111F12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON DELETE CASCADE');
    }
}
