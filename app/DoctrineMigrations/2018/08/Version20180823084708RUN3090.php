<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

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
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql');

        $createTableQuery = <<<'CREATETABLE'
CREATE TABLE close_out_netting_email_extra_content
(
    id INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    id_project INT(11) NOT NULL,
    lenders_content MEDIUMTEXT,
    borrower_content MEDIUMTEXT,
    added DATETIME,
    CONSTRAINT fk_close_out_netting_email_extra_content_id_project FOREIGN KEY (id_project) REFERENCES projects (id_project) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unq_close_out_netting_email_extra_content_id_project (id_project)
)
CREATETABLE;

        $this->addSql($createTableQuery);

        $addColumnQuery = <<<'ADDCOLUMN'
ALTER TABLE close_out_netting_payment
    ADD COLUMN lenders_notified BOOLEAN NOT NULL AFTER notified,
    ADD COLUMN borrower_notified BOOLEAN NOT NULL AFTER lenders_notified,
    ADD COLUMN id_email_content INT(11) AFTER borrower_notified,
    ADD CONSTRAINT fk_close_out_netting_payment_id_email_content FOREIGN KEY (id_email_content) REFERENCES close_out_netting_email_extra_content (id)
ADDCOLUMN;

        $this->addSql($addColumnQuery);

        $this->addSql('UPDATE close_out_netting_payment SET lenders_notified = notified, borrower_notified = notified');

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
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        $this->addSql('DROP TABLE close_out_netting_email_extra_content');

        $this->addSql('ALTER TABLE close_out_netting_payment ADD COLUMN notified TINYINT(1) NOT NULL AFTER paid_commission_tax_incl');

        $this->addSql('UPDATE close_out_netting_payment SET notified = lenders_notified');

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