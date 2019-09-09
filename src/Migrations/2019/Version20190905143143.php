<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190905143143 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-333';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "account-init", "cgu-section", "CGU", NOW())');
        $cguLabel = <<<'HTML'
J''accepte les <a href="%currentCGUPath%">CGU</a>
HTML;
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'account-init', 'cgu-label', '{$cguLabel}', NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "account-init" and name = "cgu-section"');
        $this->addSql('DELETE FROM translations WHERE section = "account-init" and name = "cgu-label"');
    }
}
