<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200609142947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1353 Participation model changement';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC73C1DBA1');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAF0564C89');
        $this->addSql('ALTER TABLE tranche_offer_fee DROP FOREIGN KEY FK_92989083F0564C89');
        $this->addSql('CREATE TABLE zz_versioned_project_participation_tranche (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(255) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', username VARCHAR(255) DEFAULT NULL, INDEX IDX_670B8E58A78D87A7 (logged_at), INDEX IDX_670B8E58F85E0677 (username), INDEX IDX_670B8E58232D562B69684D7DBF1CD3C3 (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE zz_versioned_project_participation (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(255) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', username VARCHAR(255) DEFAULT NULL, INDEX IDX_85175A86A78D87A7 (logged_at), INDEX IDX_85175A86F85E0677 (username), INDEX IDX_85175A86232D562B69684D7DBF1CD3C3 (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_participation_tranche (id INT AUTO_INCREMENT NOT NULL, id_tranche INT NOT NULL, id_project_participation INT NOT NULL, added_by INT NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', invitation_reply_added DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', invitation_reply_money_amount NUMERIC(15, 2) DEFAULT NULL, invitation_reply_money_currency VARCHAR(3) DEFAULT NULL, allocation_added DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', allocation_money_amount NUMERIC(15, 2) DEFAULT NULL, allocation_money_currency VARCHAR(3) DEFAULT NULL, INDEX IDX_6B56B4CBB8FAF130 (id_tranche), INDEX IDX_6B56B4CBAE73E249 (id_project_participation), INDEX IDX_6B56B4CB699B6BAF (added_by), UNIQUE INDEX UNIQ_6B56B4CBB8FAF130AE73E249 (id_tranche, id_project_participation), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_participation_status (id INT AUTO_INCREMENT NOT NULL, id_project_parcitipation INT NOT NULL, added_by INT NOT NULL, status SMALLINT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2786D0961D7F40EA (id_project_parcitipation), INDEX IDX_2786D096699B6BAF (added_by), INDEX IDX_2786D0967B00651C1D7F40EA (status, id_project_parcitipation), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_participation_tranche ADD CONSTRAINT FK_6B56B4CBB8FAF130 FOREIGN KEY (id_tranche) REFERENCES tranche (id)');
        $this->addSql('ALTER TABLE project_participation_tranche ADD CONSTRAINT FK_6B56B4CBAE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id)');
        $this->addSql('ALTER TABLE project_participation_tranche ADD CONSTRAINT FK_6B56B4CB699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE project_participation_status ADD CONSTRAINT FK_2786D0961D7F40EA FOREIGN KEY (id_project_parcitipation) REFERENCES project_participation (id)');
        $this->addSql('ALTER TABLE project_participation_status ADD CONSTRAINT FK_2786D096699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql(
            <<<'ProjectParticipation'
ALTER TABLE project_participation
  ADD id_current_status                                        INT            DEFAULT NULL,
  ADD committee_status                                         VARCHAR(30)    NOT NULL,
  ADD committee_deadline                                       DATE           DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
  ADD participant_last_consulted                               DATE           DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
  ADD interest_request_added                                   DATETIME       DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  ADD interest_request_fee_type                                VARCHAR(50)    DEFAULT NULL,
  ADD interest_request_fee_comment                             LONGTEXT       DEFAULT NULL,
  ADD interest_request_fee_rate                                NUMERIC(5, 4)  DEFAULT NULL,
  ADD interest_request_fee_recurring                           TINYINT(1)     DEFAULT NULL,
  ADD interest_request_min_money_amount                        NUMERIC(15, 2) DEFAULT NULL,
  ADD interest_request_min_money_currency                      VARCHAR(3)     DEFAULT NULL,
  ADD interest_request_money_amount                            NUMERIC(15, 2) DEFAULT NULL,
  ADD interest_request_money_currency                          VARCHAR(3)     DEFAULT NULL,
  ADD interest_reply_added                                     DATETIME       DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  ADD interest_reply_money_amount                              NUMERIC(15, 2) DEFAULT NULL,
  ADD interest_reply_money_currency                            VARCHAR(3)     DEFAULT NULL,
  ADD invitation_request_added                                 DATETIME       DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  ADD invitation_request_fee_type                              VARCHAR(50)    DEFAULT NULL,
  ADD invitation_request_fee_comment                           LONGTEXT       DEFAULT NULL,
  ADD invitation_request_fee_rate                              NUMERIC(5, 4)  DEFAULT NULL,
  ADD invitation_request_fee_recurring                         TINYINT(1)     DEFAULT NULL,
  ADD allocation_fee_type                                      VARCHAR(50)    DEFAULT NULL,
  ADD allocation_fee_comment                                   LONGTEXT       DEFAULT NULL,
  ADD allocation_fee_rate                                      NUMERIC(5, 4)  DEFAULT NULL,
  ADD allocation_fee_recurring                                 TINYINT(1)     DEFAULT NULL,
  DROP current_status,
  CHANGE invitation_amount invitation_request_money_amount     NUMERIC(15, 2) DEFAULT NULL,
  CHANGE invitation_currency invitation_request_money_currency VARCHAR(3)     DEFAULT NULL
ProjectParticipation
        );
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC4754941AF0274 FOREIGN KEY (id_current_status) REFERENCES project_participation_status (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7FC4754941AF0274 ON project_participation (id_current_status)');
        $this->addSql('DROP INDEX IDX_BF5476CAF0564C89 ON notification');
        $this->addSql('ALTER TABLE notification CHANGE id_tranche_offer id_project_participation_tranche INT DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAF263895D FOREIGN KEY (id_project_participation_tranche) REFERENCES project_participation_tranche (id)');
        $this->addSql('CREATE INDEX IDX_BF5476CAF263895D ON notification (id_project_participation_tranche)');

        $this->addSql(
            'UPDATE project_participation pp
                 INNER JOIN project_participation_fee ppf ON pp.id = ppf.id_project_participation
                 SET
                   pp.invitation_request_fee_rate      = ppf.fee_rate,
                   pp.invitation_request_fee_type      = ppf.fee_type,
                   pp.invitation_request_fee_comment   = ppf.fee_comment,
                   pp.invitation_request_fee_recurring = ppf.fee_recurring'
        );

        $this->addSql(
            'INSERT INTO project_participation_tranche (id_tranche, id_project_participation, added_by, updated, added, invitation_reply_added, invitation_reply_money_amount, invitation_reply_money_currency)
                 SELECT t.id_tranche, ppo.id_project_participation, ppo.added_by, NOW(), ppo.added, ppo.updated, t.money_amount, t.money_currency FROM project_participation_offer ppo
                 INNER JOIN tranche_offer t ON t.id_project_participation_offer = ppo.id;'
        );

        $this->addSql('INSERT INTO project_participation_status (id_project_parcitipation, added_by, status, added) SELECT id, pp.added_by, 10, pp.added FROM project_participation pp');
        $this->addSql('UPDATE project_participation pp INNER JOIN project_participation_status pps ON pp.id = pps.id_project_parcitipation SET pp.id_current_status = pps.id');

        $this->addSql('DROP TABLE project_participation_fee');
        $this->addSql('DROP TABLE project_participation_offer');
        $this->addSql('DROP TABLE tranche_offer');
        $this->addSql('DROP TABLE tranche_offer_fee');
        $this->addSql('DROP TABLE zz_versioned_project_participation_fee');
        $this->addSql('DROP TABLE zz_versioned_tranche_offer');
        $this->addSql('DROP TABLE zz_versioned_project_offer');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is for development proposal. Once in production, due to the migration of data, we can not go back to the old version.
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAF263895D');
        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC4754941AF0274');
        $this->addSql('CREATE TABLE project_participation_fee (id INT AUTO_INCREMENT NOT NULL, id_project_participation INT NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', fee_type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, fee_comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, fee_rate NUMERIC(5, 4) NOT NULL, fee_recurring TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_28BEA4AE73E249 (id_project_participation), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE project_participation_offer (id INT AUTO_INCREMENT NOT NULL, id_project_participation INT NOT NULL, added_by INT NOT NULL, updated_by INT DEFAULT NULL, committee_status VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, expected_committee_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1C09098516FE72E1 (updated_by), INDEX IDX_1C090985699B6BAF (added_by), INDEX IDX_1C090985AE73E249 (id_project_participation), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tranche_offer (id INT AUTO_INCREMENT NOT NULL, id_tranche INT NOT NULL, id_project_participation_offer INT NOT NULL, added_by INT NOT NULL, updated_by INT DEFAULT NULL, status VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', rate_index_type VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, rate_margin NUMERIC(4, 4) NOT NULL, rate_floor NUMERIC(4, 4) DEFAULT NULL, money_amount NUMERIC(15, 2) NOT NULL, money_currency VARCHAR(3) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, rate_floor_type VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_4E7E9DEC16FE72E1 (updated_by), INDEX IDX_4E7E9DEC699B6BAF (added_by), INDEX IDX_4E7E9DEC73C1DBA1 (id_project_participation_offer), INDEX IDX_4E7E9DECB8FAF130 (id_tranche), UNIQUE INDEX UNIQ_4E7E9DECB8FAF13073C1DBA1 (id_tranche, id_project_participation_offer), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tranche_offer_fee (id INT AUTO_INCREMENT NOT NULL, id_tranche_offer INT NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', fee_type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, fee_comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, fee_rate NUMERIC(5, 4) NOT NULL, fee_recurring TINYINT(1) NOT NULL, INDEX IDX_92989083F0564C89 (id_tranche_offer), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE zz_versioned_project_participation_fee (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, logged_at DATETIME NOT NULL, object_id VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, version INT NOT NULL, data LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_EA6ACCF7232D562B69684D7DBF1CD3C3 (object_id, object_class, version), INDEX IDX_EA6ACCF7A78D87A7 (logged_at), INDEX IDX_EA6ACCF7F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE zz_versioned_tranche_offer (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, logged_at DATETIME NOT NULL, object_id VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, version INT NOT NULL, data LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_38D147AE232D562B69684D7DBF1CD3C3 (object_id, object_class, version), INDEX IDX_38D147AEA78D87A7 (logged_at), INDEX IDX_38D147AEF85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE project_participation_fee ADD CONSTRAINT FK_28BEA4AE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C09098516FE72E1 FOREIGN KEY (updated_by) REFERENCES staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C090985699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C090985AE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC16FE72E1 FOREIGN KEY (updated_by) REFERENCES staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC73C1DBA1 FOREIGN KEY (id_project_participation_offer) REFERENCES project_participation_offer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DECB8FAF130 FOREIGN KEY (id_tranche) REFERENCES tranche (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche_offer_fee ADD CONSTRAINT FK_92989083F0564C89 FOREIGN KEY (id_tranche_offer) REFERENCES tranche_offer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE zz_versioned_project_participation_tranche');
        $this->addSql('DROP TABLE zz_versioned_project_participation');
        $this->addSql('DROP TABLE project_participation_tranche');
        $this->addSql('DROP TABLE project_participation_status');
        $this->addSql('DROP INDEX IDX_BF5476CAF263895D ON notification');
        $this->addSql('ALTER TABLE notification CHANGE id_project_participation_tranche id_tranche_offer INT DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAF0564C89 FOREIGN KEY (id_tranche_offer) REFERENCES tranche_offer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_BF5476CAF0564C89 ON notification (id_tranche_offer)');
        $this->addSql('DROP INDEX UNIQ_7FC4754941AF0274 ON project_participation');
        $this->addSql('ALTER TABLE project_participation ADD current_status INT DEFAULT 0 NOT NULL, ADD invitation_amount NUMERIC(15, 2) DEFAULT NULL, ADD invitation_currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP id_current_status, DROP committee_status, DROP committee_deadline, DROP participant_last_consulted, DROP interest_request_added, DROP interest_request_min_money_amount, DROP interest_request_min_money_currency, DROP interest_request_fee_type, DROP interest_request_fee_comment, DROP interest_request_fee_rate, DROP interest_request_fee_recurring, DROP interest_request_money_amount, DROP interest_request_money_currency, DROP interest_reply_added, DROP interest_reply_money_amount, DROP interest_reply_money_currency, DROP invitation_request_added, DROP invitation_request_fee_type, DROP invitation_request_fee_comment, DROP invitation_request_fee_rate, DROP invitation_request_fee_recurring, DROP invitation_request_money_amount, DROP invitation_request_money_currency, DROP allocation_fee_type, DROP allocation_fee_comment, DROP allocation_fee_rate, DROP allocation_fee_recurring');
    }
}
