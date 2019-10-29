<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191029164524 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-483 (Move projectFee to projectParticipation)';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM translations WHERE section = 'project-edit' AND name = 'fees-edition-confirmation-button-label'");
        $this->addSql("DELETE FROM translations WHERE section = 'project-edit' AND name = 'fees-section-title'");
        $this->addSql("DELETE FROM translations WHERE section = 'project-request' AND name = 'fees-section-title'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'project-edit', 'fees-edition-confirmation-button-label', 'Valider', '2019-10-29 14:53:18')");
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'project-edit', 'fees-section-title', 'Frais ou commissions', '2019-10-29 14:53:18')");
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'project-request', 'fees-section-title', 'Frais ou commissions', '2019-10-29 14:53:18')");
    }
}
