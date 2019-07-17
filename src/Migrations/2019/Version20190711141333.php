<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190711141333 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-241 Update repayment types';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "repayment-type"');

        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "repayment-type", "amortizable", "Amortissable", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "repayment-type", "in_fine", "In Fine", NOW())');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "repayment-type"');

        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "repayment-type", "amortizable", "Amortissable", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "repayment-type", "balloon", "Balloon", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "repayment-type", "bullet", "Bullet", NOW())');
    }
}
