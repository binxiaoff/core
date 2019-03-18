<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190313144455 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-70 add translations';
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("INSERT IGNORE INTO translations (locale, section, name, translation, added, updated)
                            VALUES ('fr_FR', 'interest-rate-index', 'FIXED', 'Taux fixe', NOW(), null),
                                   ('fr_FR', 'interest-rate-index', 'EURIBOR', 'EURIBOR', NOW(), null),
                                   ('fr_FR', 'interest-rate-index', 'EONIA', 'EONIA', NOW(), null),
                                   ('fr_FR', 'interest-rate-index', 'SONIA', 'SONIA', NOW(), null),
                                   ('fr_FR', 'interest-rate-index', 'LIBOR', 'LIBOR', NOW(), null),
                                   ('fr_FR', 'interest-rate-index', 'CHFTOIS', 'CHFTOIS', NOW(), null),
                                   ('fr_FR', 'interest-rate-index', 'FFER', 'FFER', NOW(), null),
                                   ('fr_FR', 'lending-form', 'amount', 'Montant', NOW(), null),
                                   ('fr_FR', 'lending-form', 'index-type', 'Taux de référence', NOW(), null),
                                   ('fr_FR', 'lending-form', 'margin', 'Marge en %', NOW(), null),
                                   ('fr_FR', 'lending-form', 'fee-type', 'Type de frais / commission', NOW(), null),
                                   ('fr_FR', 'lending-form', 'fee-rate', 'Taux de frais / commission en %', NOW(), null),
                                   ('fr_FR', 'lending-form', 'recurring', 'Récurrent', NOW(), null),
                                   ('fr_FR', 'lending-form', 'commission', 'Commission', NOW(), null);");
        $this->addSql('ALTER TABLE bids CHANGE rate_type rate_index_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE bids ADD agent TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE loans CHANGE rate_type rate_index_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE loans ADD agent TINYINT(1) NOT NULL');

    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("DELETE FROM translations WHERE section = 'interest-rate-index' AND name IN ('FIXED', 'EURIBOR', 'EONIA', 'SONIA', 'LIBOR', 'CHFTOIS', 'FFER');");
        $this->addSql("DELETE FROM translations WHERE section = 'lending-form' AND name IN ('amount', 'index-type', 'margin', 'fee-type', 'fee-rate', 'recurring', 'commission');");
        $this->addSql('ALTER TABLE bids CHANGE rate_indice rate_index_type VARCHAR(20) NOT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE bids DROP agent');
        $this->addSql('ALTER TABLE loans CHANGE rate_indice rate_index_type VARCHAR(20) NOT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE loans DROP agent');
    }
}
