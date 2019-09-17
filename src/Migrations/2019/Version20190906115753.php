<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190906115753 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-334';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "service-terms", "confirm", "Confirmer", NOW())');
        $label = <<<'HTML'
Je certifie avoir pris connaissance et accepter expressément <a href="%serviceTermsURI%">les conditions générales d''utilisation de CALS</a>.
HTML;
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'service-terms', 'label', '{$label}', NOW())");
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "service-terms", "title-evolution", "Les conditions générales d‘utilisation évoluent.", NOW())');

        $this->addSql('DELETE FROM translations where section = "service-terms-popup"');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations where section = "service-terms" AND name = "accept"');
        $this->addSql('DELETE FROM translations where section = "service-terms" AND name = "label"');
        $this->addSql('DELETE FROM translations where section = "service-terms" AND name = "title-evolution"');
        $label = <<<'HTML'
Je certifie avoir pris connaissance et accepter expressément <a href="/conditions-service">les conditions générales d''utilisation de CALS</a>.
HTML;
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added, updated) VALUES ('fr_FR', 'service-terms-popup', 'confirm-check-box-label', '{$label}', NOW()");
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added, updated) VALUES ('fr_FR', 'service-terms-popup', 'confirmation-button-label', 'Confirmer', NOW()");
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added, updated) VALUES ('fr_FR', 'service-terms-popup', 'title', 'Les conditions générales d‘utilisation évoluent.', NOW()");
    }
}
