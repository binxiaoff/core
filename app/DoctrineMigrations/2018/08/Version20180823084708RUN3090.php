<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180823084708RUN3090 extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $insertTranslationsQuery = <<<'TRANSLATIONS'
INSERT INTO translations(locale, section, name, translation, added) VALUES
  ('fr_FR', 'bo-closeout-netting-popup', 'email-lenders-choice', 'Envoyer un mail aux prêteurs', NOW()),
  ('fr_FR', 'bo-closeout-netting-popup', 'email-borrower-choice', 'Envoyer un mail à l\'emprunteur', NOW()),
  ('fr_FR', 'bo-closeout-netting-popup', 'email-lender-textarea', 'Ajouter du contenu spécifique', NOW()),
  ('fr_FR', 'bo-closeout-netting-popup', 'email-borrower-textarea', 'Ajouter du contenu spécifique', NOW()),
  ('fr_FR', 'bo-closeout-netting-popup', 'email-disabled-message', 'La socièté étant en %companyStatus%, vous ne pouvez pas envoyer de mail.', NOW())
TRANSLATIONS;

        $this->addSql($insertTranslationsQuery);

        $createTableQuery = <<<'CREATETABLE'
CREATE TABLE close_out_netting_email_extra_content
(
    id INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    id_project INT(11) NOT NULL,
    lenders_content MEDIUMTEXT,
    borrower_content MEDIUMTEXT,
    added DATETIME,
    CONSTRAINT fk_close_out_netting_email_extra_content_id_project FOREIGN KEY (id_project) REFERENCES projects (id_project) ON DELETE CASCADE ON UPDATE CASCADE
)
CREATETABLE;

        $this->addSql($createTableQuery);

        $addColumnQuery = <<<'ADDCOLUMN'
ALTER TABLE close_out_netting_payment
    ADD COLUMN lenders_notified BOOLEAN NOT NULL AFTER notified,
    ADD COLUMN borrower_notified BOOLEAN NOT NULL AFTER lenders_notified,
    ADD COLUMN id_email_content INT(11) NULL DEFAULT NULL AFTER borrower_notified,
    ADD CONSTRAINT fk_close_out_netting_payment_id_email_content FOREIGN KEY (id_email_content) REFERENCES close_out_netting_email_extra_content (id)
ADDCOLUMN;

        $this->addSql($addColumnQuery);

        // No rollback query needed
        $this->addSql('UPDATE close_out_netting_payment SET lenders_notified = notified, borrower_notified = notified');

        $this->addSql('UPDATE mail_templates SET type = \'emprunteur-projet-recouvrement\' WHERE type = \'emprunteur-projet-statut-recouvrement\'');

        $this->addSql('UPDATE mail_templates SET type = \'preteur-projet-recouvrement\' WHERE type = \'preteur-projet-statut-recouvrement\'');

        $this->addSql('ALTER TABLE close_out_netting_payment DROP COLUMN notified');

        $this->addSql('ANALYZE TABLE close_out_netting_payment');
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $deleteTranslationsQuery = <<<'TRANSLATIONSTODELETE'
DELETE FROM translations
 WHERE section = 'bo-closeout-netting-popup'
 AND unilend.translations.name IN ('email-lenders-choice', 'email-borrower-choice', 'email-lender-textarea', 'email-borrower-textarea', 'email-disabled-message')
TRANSLATIONSTODELETE;

        $this->addSql($deleteTranslationsQuery);

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        $this->addSql('DROP TABLE close_out_netting_email_extra_content');

        $this->addSql('ALTER TABLE close_out_netting_payment ADD COLUMN notified TINYINT(1) NOT NULL AFTER paid_commission_tax_incl');

        $this->addSql('UPDATE close_out_netting_payment SET notified = lenders_notified');

        $this->addSql('UPDATE mail_templates SET type = \'emprunteur-projet-statut-recouvrement\' WHERE type = \'emprunteur-projet-recouvrement\'');

        $this->addSql('UPDATE mail_templates SET type = \'preteur-projet-statut-recouvrement\' WHERE type = \'preteur-projet-recouvrement\'');

        $dropColumnQuery = <<<'DROPCOLUMN'
ALTER TABLE close_out_netting_payment
    DROP COLUMN lenders_notified,
    DROP COLUMN borrower_notified,
    DROP FOREIGN KEY fk_close_out_netting_payment_id_email_content,
    DROP COLUMN id_email_content
DROPCOLUMN;

        $this->addSql($dropColumnQuery);

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');

        $this->addSql('ANALYZE TABLE close_out_netting_payment');
    }
}