<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190506125030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-104 Data and translations';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            <<<'TRANSLATIONS'
            INSERT INTO translations (locale, section, name, translation, added) 
            VALUES
                   ('fr_FR', 'project-request', 'title', 'Déposer un dossier', NOW()),
                   ('fr_FR', 'project-request', 'guarantee-section-title', 'Garantie', NOW()),
                   ('fr_FR', 'project-request', 'tranches-section-title', 'Tranches', NOW()),
                   ('fr_FR', 'project-request', 'structuration-section-title', 'Structuration', NOW()),
                   ('fr_FR', 'project-request', 'attachments-section-title', 'Documents', NOW()),
                   ('fr_FR', 'project-request', 'confirmation-button', 'Déposer le dossier', NOW()),
                   ('fr_FR', 'market-segment', 'public_collectivity', 'Collectivités Publiques', NOW()),
                   ('fr_FR', 'market-segment', 'energy', 'Énergie', NOW()),
                   ('fr_FR', 'market-segment', 'corporate', 'Corporate', NOW()),
                   ('fr_FR', 'market-segment', 'lbo', 'LBO', NOW()),
                   ('fr_FR', 'market-segment', 'real_estate_development', 'Promotion immobilière', NOW()),
                   ('fr_FR', 'market-segment', 'infrastructure', 'Infrastructure', NOW()),
                   ('fr_FR', 'foncaris-guarantee', 'foncaris_guarantee_no_need', 'Aucune demande', NOW()),
                   ('fr_FR', 'foncaris-guarantee', 'foncaris_guarantee_need', 'Faire une demande de garantie auprès de FONCARIS', NOW()),
                   ('fr_FR', 'foncaris-guarantee', 'foncaris_guarantee_already_guaranteed', 'Ce dossier fait déjà l‘objet d‘une demande de garantie auprès de FONCARIS', NOW()),
                   ('fr_FR', 'money-form', 'amount', 'Montant',  NOW()),
                   ('fr_FR', 'money-form', 'currency', 'Devise', NOW()),
                   ('fr_FR', 'tranche-form', 'name', 'Titre', NOW()),
                   ('fr_FR', 'tranche-form', 'repayment-type', 'Type', NOW()),
                   ('fr_FR', 'tranche-form', 'maturity', 'Maturité', NOW()),
                   ('fr_FR', 'tranche-form', 'capital-periodicity', 'Périodicité capital (mois)', NOW()),
                   ('fr_FR', 'tranche-form', 'interest-periodicity', 'Périodicité intérêts (mois)', NOW()),
                   ('fr_FR', 'tranche-form', 'expected-releasing-date', 'Date de déblocage de fonds envisagée', NOW()),
                   ('fr_FR', 'tranche-form', 'expected-starting-date', 'Date de première échéance envisagée', NOW()),
                   ('fr_FR', 'tranche-form', 'fees', 'Frais liés à la tranche', NOW()),
                   ('fr_FR', 'global-form', 'delete', 'Supprimer', NOW()),
                   ('fr_FR', 'percent-fee-form', 'type', 'Type', NOW()),
                   ('fr_FR', 'percent-fee-form', 'rate', 'Taux', NOW()),
                   ('fr_FR', 'percent-fee-form', 'recurring', 'Récurrent ?', NOW()),
                   ('fr_FR', 'project-form', 'title', 'Titre', NOW()),
                   ('fr_FR', 'project-form', 'borrower-company', 'Contrepartie emprunteuse', NOW()),
                   ('fr_FR', 'project-form', 'market-segment', 'Marché', NOW()),
                   ('fr_FR', 'project-form', 'replay-deadline', 'Date butoir de réponse', NOW()),
                   ('fr_FR', 'project-form', 'expected-closing-date', 'Date de closing envisagée', NOW()),
                   ('fr_FR', 'project-form', 'description', 'Description du projet', NOW()),
                   ('fr_FR', 'project-form', 'arranger', 'Arrangeur', NOW()),
                   ('fr_FR', 'project-form', 'run', 'RUN', NOW()),
                   ('fr_FR', 'attachment-form', 'type-placeholder', 'Sélectionner le type de fichier', NOW()),
                   ('fr_FR', 'attachment-form', 'description-placeholder', 'Documents', NOW()),
                   ('fr_FR', 'repayment-type', 'repayment_type_amortizing_fixed_payment', 'Échéance fixe', NOW()),
                   ('fr_FR', 'repayment-type', 'repayment_type_amortizing_fixed_capital', 'Capital fixe', NOW()),
                   ('fr_FR', 'repayment-type', 'repayment_type_non_amortizing_in_fine', 'In Fine', NOW()),
                   ('fr_FR', 'repayment-type', 'repayment_type_revolving_credit', 'CAPEX', NOW()),
                   ('fr_FR', 'interest-rate-index', 'index_fixed', 'Fixe', NOW()),
                   ('fr_FR', 'interest-rate-index', 'index_euribor', 'EURIBOR', NOW()),
                   ('fr_FR', 'interest-rate-index', 'index_eonia', 'EONIA', NOW()),
                   ('fr_FR', 'interest-rate-index', 'index_sonia', 'SONIA', NOW()),
                   ('fr_FR', 'interest-rate-index', 'index_libor', 'LIBOR', NOW()),
                   ('fr_FR', 'interest-rate-index', 'index_chftois', 'CHFTOIS', NOW()),
                   ('fr_FR', 'interest-rate-index', 'index_ffer', 'FFER', NOW()),
                   ('fr_FR', 'fee-type', 'arranging', 'Frais d‘arrangement', NOW()),
                   ('fr_FR', 'fee-type', 'repayment', 'Commission de remboursement', NOW()),
                   ('fr_FR', 'fee-type', 'non_drawing', 'Commission non tirage', NOW())
                   
TRANSLATIONS
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
