<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191028124734 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-482 (Project creation)';
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

        $this->addSql('ALTER TABLE project CHANGE offer_visibility offer_visibility VARCHAR(25) NOT NULL');
        $this->addSql("UPDATE project SET offer_visibility = 'public' WHERE offer_visibility = 1");
        $this->addSql("UPDATE project SET offer_visibility = 'private' WHERE offer_visibility = 2");
        $this->addSql('ALTER TABLE companies CHANGE name name MEDIUMTEXT NOT NULL, CHANGE siren siren VARCHAR(15) NOT NULL');
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

        $this->addSql('ALTER TABLE companies CHANGE name name MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, CHANGE siren siren VARCHAR(15) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql("UPDATE project SET offer_visibility = 1 WHERE offer_visibility = 'public'");
        $this->addSql("UPDATE project SET offer_visibility = 2 WHERE offer_visibility = 'private'");
        $this->addSql('ALTER TABLE project CHANGE offer_visibility offer_visibility SMALLINT NOT NULL');
    }
}
