<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20191004084302 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-382-type-funding';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'loan-type', 'stand_by', 'Stand by', NOW())");
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'loan-type', 'signature_commitment', 'Engagement par signature', NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM translations WHERE section = 'loan-type' AND name IN ('stand_by', 'signature_commitment')");
    }
}
