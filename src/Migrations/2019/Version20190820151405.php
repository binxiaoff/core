<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190820151405 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-263 Add offer visibility column and translations';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project ADD offer_visibility SMALLINT NOT NULL');
        $this->addSql('INSERT IGNORE INTO translations (locale, section, name, translation, added) VALUES
                            (\'fr_FR\', \'project-form\', \'offer-visibility-label\', \'Visibilité des offres\', NOW()),
                            (\'fr_FR\', \'project-form\', \'offer-visibility-choice-1-label\', \'Publique\', NOW()),
                            (\'fr_FR\', \'project-form\', \'offer-visibility-choice-2-label\', \'Privé\', NOW())');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project ADD lender_consultation_closing_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', DROP offer_visibility');
        $this->addSql('DELETE FROM translations WHERE section = \'project-form\' AND name in (\'offer-visibility-label\', \'offer-visibility-choice-label-1\', \'offer-visibility-choice-label-2\')');
    }
}
