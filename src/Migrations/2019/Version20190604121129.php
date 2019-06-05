<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190604121129 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-191 Add project roles';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'TRANSLATIONS'
INSERT IGNORE INTO translations (locale, section, name, translation, added)
VALUES
  ('fr_FR', 'project-form', 'deputy-arranger-label', 'Co-arrangeur', NOW()),
  ('fr_FR', 'project-form', 'loan-officer-label', 'Agent du crédit', NOW()),
  ('fr_FR', 'project-form', 'security-trustee-label', 'Agent des sûretés', NOW())
TRANSLATIONS
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "project-form" AND name IN ("deputy-arranger-label", "loan-officer-label", "security-trustee-label")');
    }
}
