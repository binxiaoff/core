<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190509152136 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Translations refinements';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE translations SET translation = "Garantie FONCARIS" WHERE section = "project-request" AND name = "guarantee-section-title"');
        $this->addSql('UPDATE translations SET translation = "Nom" WHERE section = "tranche-form" AND name = "name"');
        $this->addSql('UPDATE translations SET translation = "Maturité (mois)" WHERE section = "tranche-form" AND name = "maturity"');
        $this->addSql('UPDATE translations SET translation = "Déblocage des fonds" WHERE section = "tranche-form" AND name = "expected-releasing-date"');
        $this->addSql('UPDATE translations SET translation = "Première échéance" WHERE section = "tranche-form" AND name = "expected-starting-date"');

        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "percent-fee-form", "delete-row", "Supprimer les frais", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "tranche-form", "delete-row", "Supprimer la tranche", NOW())');

        $this->addSql('DELETE FROM translations WHERE section = "global-form" AND name = "delete"');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
