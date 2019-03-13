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
                            VALUES ('fr_FR', 'interest-rate-indice', 'FIXED', 'Taux fixe', NOW(), null),
                                   ('fr_FR', 'interest-rate-indice', 'EURIBOR', 'EURIBOR', NOW(), null),
                                   ('fr_FR', 'interest-rate-indice', 'EONIA', 'EONIA', NOW(), null),
                                   ('fr_FR', 'interest-rate-indice', 'SONIA', 'SONIA', NOW(), null),
                                   ('fr_FR', 'interest-rate-indice', 'LIBOR', 'LIBOR', NOW(), null),
                                   ('fr_FR', 'interest-rate-indice', 'CHFTOIS', 'CHFTOIS', NOW(), null),
                                   ('fr_FR', 'interest-rate-indice', 'FFER', 'FFER', NOW(), null);");

    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("DELETE FROM translations WHERE section = 'interest-rate-indice' AND name IN ('FIXED', 'EURIBOR', 'EONIA', 'SONIA', 'LIBOR', 'CHFTOIS', 'FFER');");
    }
}
