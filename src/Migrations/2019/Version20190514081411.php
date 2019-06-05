<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190514081411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'TRANSLATION'
INSERT INTO translations (locale, section, name, translation, added)
VALUES
  ('fr_FR', 'company', 'creation-in-progress', 'Société encore à immatriculer', NOW()),
  ('fr_FR', 'project-form', 'borrower-company-creation-in-progress-label', 'Encore à immatriculer ?', NOW()),
  ('fr_FR', 'project-form', 'borrower-company-required', 'Merci de saisir le SIREN de la contrepartie emprunteuse', NOW())
TRANSLATION
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "company" AND name = "creation-in-progress"');
    }
}
