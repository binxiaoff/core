<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190820121422 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-262 Add lender\'s consultation closing date';
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

        $this->addSql('ALTER TABLE project ADD lender_consultation_closing_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('INSERT IGNORE INTO translations (locale, section, name, translation, added) VALUES (\'fr_FR\', \'project-form\', \'lender-consultation-date-label\', \'Fin de consultation prêteur\', NOW());');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project DROP lender_consultation_closing_date');
        $this->addSql('DELETE FROM translations WHERE section = \'project-form\' AND name = \'lender-consultation-date-label\'');
    }
}
