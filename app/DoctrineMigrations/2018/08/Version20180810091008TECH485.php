<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20180810091008TECH485 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql');

        $this->addSql('INSERT INTO translations (locale, section, name, translation, added, updated) VALUES
            ("fr_FR", "loan-contract-download", "error-details-contact-link", "<p>Veuillez contacter notre service commercial en <a href=\"%contactUrl%\">cliquant ici</a>.</p>", NOW(), NOW()),
            ("fr_FR", "loan-contract-download", "cannot-find-loan-error-title", "Impossible de trouver le document", NOW(), NOW()),
            ("fr_FR", "loan-contract-download", "cannot-find-client-error-title", "Document invalide", NOW(), NOW()),
            ("fr_FR", "loan-contract-download", "wrong-client-hash-error-title", "Lien invalide", NOW(), NOW()),
            ("fr_FR", "loan-contract-download", "access-denied-error-title", "Accès interdit", NOW(), NOW()),
            ("fr_FR", "loan-contract-download", "exception-occurred-error-title", "Une erreur est survenue", NOW(), NOW()),
            ("fr_FR", "loan-contract-download", "unknown-error-title", "Une erreur est survenue", NOW(), NOW())'
        );

        $this->addSql('UPDATE underlying_contract SET document_template = "bon_de_caisse" WHERE label = "bon_de_caisse"');
        $this->addSql('UPDATE underlying_contract SET document_template = "ifp" WHERE label = "ifp"');
        $this->addSql('UPDATE underlying_contract SET document_template = "minibon",  block_slug = "" WHERE label = "minibon"');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql');

        $this->addSql('DELETE from translations WHERE section = "loan-contract-download" AND name = "error-details-contact-link"');
        $this->addSql('DELETE from translations WHERE section = "loan-contract-download" AND name = "cannot-find-loan-error-title"');
        $this->addSql('DELETE from translations WHERE section = "loan-contract-download" AND name = "cannot-find-client-error-title"');
        $this->addSql('DELETE from translations WHERE section = "loan-contract-download" AND name = "wrong-client-hash-error-title"');
        $this->addSql('DELETE from translations WHERE section = "loan-contract-download" AND name = "access-denied-error-title"');
        $this->addSql('DELETE from translations WHERE section = "loan-contract-download" AND name = "exception-occurred-error-title"');
        $this->addSql('DELETE from translations WHERE section = "loan-contract-download" AND name = "unknown-error-title"');

        $this->addSql('UPDATE underlying_contract SET document_template = "contrat_html" WHERE label = "bon_de_caisse"');
        $this->addSql('UPDATE underlying_contract SET document_template = "contrat_ifp_html" WHERE label = "ifp"');
        $this->addSql('UPDATE underlying_contract SET document_template = "contrat_minibon_html",  block_slug = "pdf-minibon" WHERE label = "minibon"');
    }
}
