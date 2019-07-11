<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190520143152 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-123 translations';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'TRANSLATIONS'
            INSERT INTO translations (locale, section, name, translation, added) VALUES
              ('fr_FR', 'bids', 'table-column-lender', 'Prêteur', NOW()),
              ('fr_FR', 'bids', 'table-column-amount', 'Montant', NOW()),
              ('fr_FR', 'bids', 'table-column-index-rate', 'Taux de référence', NOW()),
              ('fr_FR', 'bids', 'table-column-rate-margin', 'Marge', NOW()),
              ('fr_FR', 'bids', 'table-column-rate-floor', 'Flooré à', NOW()),
              ('fr_FR', 'bids', 'table-action-reject', 'Rejeter l’offre', NOW()),
              ('fr_FR', 'bids', 'table-action-modify', 'Modifier et valider l’offre', NOW()),
              ('fr_FR', 'bids', 'table-action-accept', 'Valider l’offre', NOW()),
              ('fr_FR', 'bids', 'modal-partial-title', 'Acceptation partielle de l’offre', NOW()),
              ('fr_FR', 'bids', 'modal-partial-amount-label', 'Montant', NOW()),
              ('fr_FR', 'bids', 'modal-partial-amount-help', '(max #formattedAmountPlaceholder#)', NOW()),
              ('fr_FR', 'bids', 'modal-partial-cancel-button', 'Annuler', NOW()),
              ('fr_FR', 'bids', 'modal-partial-submit-button', 'Accepter l’offre', NOW())
TRANSLATIONS
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "bids" AND name IN (
          "table-column-lender",
          "table-column-amount",
          "table-column-index-rate",
          "table-column-rate-margin",
          "table-column-rate-floor",
          "table-action-reject",
          "table-action-modify",
          "table-action-accept",
          "modal-partial-title",
          "modal-partial-amount-label",
          "modal-partial-amount-help",
          "modal-partial-cancel-button",
          "modal-partial-submit-button"
        )');
    }
}
