<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;

final class Version20190626101636 extends ContainerAwareMigration
{
    public function getDescription(): string
    {
        return 'CALS-158 Add translation for tranches and fees';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'TRANSLATIONS'
            INSERT INTO translations (locale, section, name, translation, added) VALUES
                ('fr_FR', 'fee', 'type-column-label', 'Type', NOW()),
                ('fr_FR', 'fee', 'rate-column-label', 'Taux', NOW()),
                ('fr_FR', 'fee', 'is-recurring-column-label', 'Récurrent ?', NOW()),
                ('fr_FR', 'fee', 'one-time-text', 'Non', NOW()),
                ('fr_FR', 'fee', 'recurring-text', 'Oui', NOW()),
                ('fr_FR', 'fee', 'no-fee-info-message', 'Il y n\'a pas de frais ni commissions sur ce projet.', NOW())
TRANSLATIONS
    );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
