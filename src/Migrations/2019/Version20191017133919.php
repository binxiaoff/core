<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\{DBALException, Schema\Schema};
use Unilend\Migrations\{ContainerAwareMigration, Traits\FlushTranslationCacheTrait};

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191017133919 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-456 Remplace "bid" by "tranche offer" in the translations and the mail templates';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE mail_template SET name = REPLACE(name, \'bid\', \'tranche-offer\')  WHERE name in (\'bid-accepted\', \'bid-rejected\', \'bid-submitted\')');
        $this->addSql('UPDATE translations SET name = \'tranche-offer-submitted-maker-title\' WHERE name = \'bid-submitted-bidder-title\'');
        $this->addSql('UPDATE translations SET name = \'tranche-offer-submitted-maker-content\', translation = REPLACE(translation, \'bid\', \'offer\') WHERE name = \'bid-submitted-bidder-content\'');
        $this->addSql('UPDATE translations SET name = \'tranche-offer-submitted-participants-title\' WHERE name = \'bid-submitted-lenders-title\'');
        $this->addSql('UPDATE translations SET name = \'tranche-offer-submitted-participants-content\', translation = REPLACE(translation, \'bid\', \'offer\') WHERE name = \'bid-submitted-lenders-content\'');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE mail_template SET name = REPLACE(name, \'tranche-offer\', \'bid\')  WHERE name in (\'tranche-offer-accepted\', \'tranche-offer-rejected\', \'tranche-offer-submitted\')');
        $this->addSql('UPDATE translations SET name = \'bid-submitted-bidder-title\' WHERE name = \'tranche-offer-submitted-maker-title\'');
        $this->addSql('UPDATE translations SET name = \'bid-submitted-bidder-content\', translation = REPLACE(translation, \'offer\', \'bid\') WHERE name = \'tranche-offer-submitted-maker-content\'');
        $this->addSql('UPDATE translations SET name = \'bid-submitted-lenders-title\' WHERE name = \'tranche-offer-submitted-participants-title\'');
        $this->addSql('UPDATE translations SET name = \'bid-submitted-lenders-content\', translation = REPLACE(translation, \'offer\', \'bid\') WHERE name = \'tranche-offer-submitted-participants-content\'');
    }
}
