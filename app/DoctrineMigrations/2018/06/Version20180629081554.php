<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20180629081554 extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $insertTranslations = <<<'TRANSLATIONS'
INSERT IGNORE INTO translations (locale, section, name, translation, added, updated) VALUES
  ('fr_FR', 'lender-data-update', 'natural-person-start-page-instruction', '<p>Bonjour %firstName%,</p><p>Afin de conserver d''un compte prêteur à jour, merci de vous assurer que les informations personnelles dont nous disposons correspondent à votre situation actuelle.</p><p>Unilend garantit à l''ensemble de ses prêteurs la sécurité et la confidentialité de leurs données personnelles qui ne sont jamais communiquées à des tiers non habilités.</p>', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'natural-person-start-button', 'Vérifier mes informations personnelles', NOW(), NOW()),
  ('fr_FR', 'lender-profile', 'tab-title-data-update', 'Mise à jour mes informations personnelles', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'identity-section-title', 'Votre identité', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'modify-button', 'Modifier', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'continue-button', 'Continuer', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'confirm-button', 'Confirmer', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'save-button', 'Enregistrer', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'cancel-button', 'Annuler l''édition', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'finish-later', 'Je finirai la mise à jour de mes données lors de ma prochaine connection.', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'id-doc-section-title', 'Votre pièce d''identité', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'id-doc-number', 'Passeport / Carte Nationale d''identité', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'id-doc-expiration-date', 'Valide jusqu''au', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'id-doc-last-upload-date', 'Date du dernier téléchargement de votre justificatif d''identité', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'id-doc-validated', '<p>Vous avez changé d''étqt-civil (par exemple : mariage) ?</p><p>Merci de mettre à jour votre nom d''usage. Un justificatif (carte nationale d''identité ou passsport est nécessaire) pour valider ce changement.', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'id-doc-expired', 'Votre justificatif d''identité n''est plus valable. Merci de nous transmettre la copie d''un justificatif d''identité en cours de validité.', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'id-doc-non-checked', '<p>Si cette pièce d''identité n''est plus valable ou qu''elle est expirée, merci de mettre à jour ce document.</p><p>Si ce document est toujours valable, cliquez sur "Continuer".</p>', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'telephone-section-title', 'Vos coordonnées téléphoniques', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'telephone-description', '<p>Votre numéro de téléphone ne sera jamais transmis à des tiers. Cette information nous permet de vous contacter au sujet de votre compte et de vous transmettre des informations de connexion à votre compte.</p><p>Nous ne communiquons pas cette information à des tiers. Votre numéro de mobile permet notre équipe de relation prêteurs d''entrer en contact avec vous. En cas d''oubli de mot de passe nous pourrons vous transmettre un code de vérification par SMS.</p>', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'main-address-section-title', 'Votre adresse fiscale', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'bank-account-section-title', 'Vos information bancaires', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'bank-account-description', '<p>C''est le compte bancaire vers lequel vous retirez l''argent disponible sur votre compte Unilend.</p>', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'funds-origin-section-title', 'Origine des fonds', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'funds-origin-description', '<p>Merci de confirmer l''origine des fonds que vous déposez sur Unilend.</p>', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'details-page-title', 'Mise à jour vos données personnelles', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'end-page-instruction', '<p>Merci d''avoir confirmé vos informations personnelles. Conformément à la règlementation en vigueur nous vous demanderons à nouveau de confirmer ces données d''ici quelques mois.</p><p>Vous pouvez consulter et mettre à jour vos informations à tout moment depuis votre espace personnel.</p>', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'return-to-profile-button', 'Retourner sur mon compte', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'start-cip-survey-button', 'Répondre à notre questionnaire de conseil', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'legal-entity-start-page-instruction', '<p>Bonjour %firstName%,</p><p>Afin de conserver un compte prêteur à jour, merci de vous assurer que les informations dont nous disposons correspondent à la situation actuelle de %company_name% dont vous êtes le représentant légal.</p><p>Unilend garantit à l''ensemble de ses prêteurs la sécurité et la confidentialité de leurs données personnelles qui ne sont jamais communiquées à des tiers non habilités.</p>', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'legal-entity-start-button', 'Vérifier les informations du compte de %company_name%', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'legal-entity-identity-section-title', 'Raison sociale', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'legal-entity-main-address-section-title', 'Adresse fiscale de %company_name%', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'legal-entity-bank-account-section-title', 'Informations bancaires de %company_name%', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'legal-entity-bank-account-description', 'C''est le compte bancaire vers le quel vous retirez l''argent disponible sur le compte Unilend de %company_name%', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'legal-entity-funds-origin-section-title', 'Origine des fonds de %company_name%', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'legal-representative-section-title', 'Identité du représentant légal de %company_name%', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'kbis-doc-section-title', 'Extrait de Kbis de %company_name%', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'kbis-doc-description', '<p>Date du dernier téléchargement de votre justificatif : %upload_date%</p><p>Si des changements sont intervenus depuis cette date, merci de nous transmettre la copie d''un nouvel extrait de Kbis', NOW(), NOW()),
  ('fr_FR', 'lender-data-update', 'legal-entity-end-page-instruction', 'Merci d''avoir confirmé les informations de %company_name%. Conformément à la règlementation en vigueur nous vous demanderons à nouveau de confirmer ces données d''ici quelques mois.', NOW(), NOW())
TRANSLATIONS;

        $this->addSql($insertTranslations);

    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $deleteTranslations = <<<'TRANSLATIONSTODELETE'
DELETE FROM translations
 WHERE section = 'lender-data-update'
AND name in ('natural-person-start-page-instruction', 'natural-person-start-button', 'identity-section-title', 'modify-button', 'continue-button', 'confirm-button', 'save-button', 'cancel-button', 'finish-later', 'id-doc-section-title', 'id-doc-number', 'id-doc-expiration-date', 'id-doc-last-upload-date', 'id-doc-validated', 'id-doc-expired', 'id-doc-non-checked', 'telephone-section-title', 'telephone-description', 'main-address-section-title', 'bank-account-section-title', 'bank-account-description', 'funds-origin-section-title', 'funds-origin-description', 'details-page-title', 'end-page-instruction', 'return-to-profile-button', 'start-cip-survey-button', 'legal-entity-start-page-instruction', 'legal-entity-start-button', 'legal-entity-identity-section-title', 'legal-entity-main-address-section-title', 'legal-entity-bank-account-section-title', 'legal-entity-bank-account-description', 'legal-entity-funds-origin-section-title', 'legal-representative-section-title', 'kbis-doc-section-title', 'kbis-doc-description', 'legal-entity-end-page-instruction')
TRANSLATIONSTODELETE;

        $this->addSql($deleteTranslations);
        $this->addSql('DELETE FROM translations WHERE section = \'lender-profile\' AND name = \'tab-title-data-update\'');
    }
}
