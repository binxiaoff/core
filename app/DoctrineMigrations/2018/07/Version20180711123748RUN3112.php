<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;


final class Version20180711123748RUN3112 extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO operation_type (label) VALUES (\'lender_withdraw_cancel\')');
        $this->addSql('INSERT IGNORE INTO translations (locale, section, name, translation, added, updated) VALUES (\'fr_FR\', \'lender-operations\', \'operation-label-lender_withdraw_cancel\', \'Echec de retrait d\'\'argent\', NOW(), NOW())');


    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM operation_type WHERE label = \'lender_withdraw_cancel\'');
        $this->addSql('DELETE FROM translations WHERE section = \'lender-operations\' AND name = \'operation-label-lender_withdraw_cancel\'');
    }
}
