<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\{DBALException, Schema\Schema};
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190722143644 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-119 NDA acceptance';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE project_confidentiality_acceptance (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, id_client INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1C7FA7F2F12E799E (id_project), INDEX IDX_1C7FA7F2E173B1B8 (id_client), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_confidentiality_acceptance ADD CONSTRAINT FK_1C7FA7F2F12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_confidentiality_acceptance ADD CONSTRAINT FK_1C7FA7F2E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client)');

        $this->addSql('ALTER TABLE project ADD confidential TINYINT(1) NOT NULL DEFAULT 0 AFTER description, ADD confidentiality_disclaimer LONGTEXT DEFAULT NULL AFTER confidential');

        $this->addSql(
            <<<'INSERTTRANS'
INSERT INTO translations (locale, section, name, translation, added) VALUES
  ('fr_FR', 'project-edit', 'confidentiality-section-title', 'Confidentialité', NOW()),
  ('fr_FR', 'project-edit', 'confidentiality-section-info', 'Vous pouvez soumettre les utilisateurs à un accord de confidentialité spécifique avant de les laisser accéder au dossier. Dans ce cas, ils devront signer l’accord de confidentialité avant de pouvoir visualiser les informations et documents relatifs au dossier.', NOW()),
  ('fr_FR', 'confidentiality-form', 'confidential-label', 'Faire signer un accord de confidentialité avant l’accès au dossier', NOW()),
  ('fr_FR', 'confidentiality-form', 'disclaimer-placeholder', 'Description de l’accord de confidentialité à faire signer.', NOW()),
  ('fr_FR', 'confidentiality-form', 'submit-button-label', 'Modifier les paramètres de confidentialité', NOW()),
  ('fr_FR', 'project-confidentiality', 'page-title', 'Accord de confidentialité', NOW()),
  ('fr_FR', 'project-confidentiality', 'submit-button-label', 'Je m’engage à respecter l’accord de confidentialité', NOW())
INSERTTRANS
        );
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP TABLE project_confidentiality_acceptance');

        $this->addSql('ALTER TABLE project DROP confidential, DROP confidentiality_disclaimer');

        $this->addSql('DELETE FROM translations WHERE section = "project-edit" AND name IN ("confidentiality-section-title", "confidentiality-section-info")');
        $this->addSql('DELETE FROM translations WHERE section = "confidentiality-form" AND name IN ("confidential-label", "disclaimer-placeholder", "submit-button-label")');
        $this->addSql('DELETE FROM translations WHERE section = "project-confidentiality"');
    }
}
