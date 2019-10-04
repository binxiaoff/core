<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20191004092411 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-383-repayment-terms';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'repayment-type', 'atypical', 'Atypique', NOW())");
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'repayment-type', 'constant_capital', 'Capital constant', NOW())");
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'repayment-type', 'repayment_fixed', 'Échéances constantes', NOW())");
        $this->addSql("DELETE FROM translations WHERE section = 'repayment-type' AND name = 'amortizable'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM translations WHERE section = 'repayment-type' AND name IN ('atypical', 'constant_capital', 'repayment_fixed')");
        $this->addSql("INSERT INTO translations (id_translation, locale, section, name, translation, added) VALUES (191, 'fr_FR', 'repayment-type', 'amortizable', 'Amortissable', '2019-08-23 15:52:37')");
    }
}
