<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190613085803 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Add EURIBOR variations';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "interest-rate-index"');
        $this->addSql(
            <<<'TRANSLATIONS'
INSERT INTO translations (locale, section, name, translation, added)
VALUES
  ('fr_FR', 'interest-rate-index', 'index_fixed', 'Fixe', NOW()), 
  ('fr_FR', 'interest-rate-index', 'index_euribor_1_month', 'E1M', NOW()), 
  ('fr_FR', 'interest-rate-index', 'index_euribor_3_months', 'E3M', NOW()),
  ('fr_FR', 'interest-rate-index', 'index_euribor_6_months', 'E6M', NOW()), 
  ('fr_FR', 'interest-rate-index', 'index_euribor_12_months', 'E12M', NOW()), 
  ('fr_FR', 'interest-rate-index', 'index_eonia', 'EONIA', NOW()),
  ('fr_FR', 'interest-rate-index', 'index_sonia', 'SONIA', NOW()), 
  ('fr_FR', 'interest-rate-index', 'index_libor', 'LIBOR', NOW()), 
  ('fr_FR', 'interest-rate-index', 'index_chftois', 'CHFTOIS', NOW()),
  ('fr_FR', 'interest-rate-index', 'index_ffer', 'FFER', NOW()), 
  ('fr_FR', 'interest-rate-index',  '', '', NOW()) 
TRANSLATIONS
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "interest-rate-index"');
        $this->addSql(
            <<<'TRANSLATIONS'
INSERT INTO translations (locale, section, name, translation, added) 
VALUES 
  ('fr_FR', 'interest-rate-index', 'index_fixed', 'Fixe', NOW()),
  ('fr_FR', 'interest-rate-index', 'index_euribor', 'EURIBOR', NOW()),
  ('fr_FR', 'interest-rate-index', 'index_eonia', 'EONIA', NOW()),
  ('fr_FR', 'interest-rate-index', 'index_sonia', 'SONIA', NOW()),
  ('fr_FR', 'interest-rate-index', 'index_libor', 'LIBOR', NOW()),
  ('fr_FR', 'interest-rate-index', 'index_chftois', 'CHFTOIS', NOW()),
  ('fr_FR', 'interest-rate-index', 'index_ffer', 'FFER', NOW())
TRANSLATIONS
        );
    }
}
