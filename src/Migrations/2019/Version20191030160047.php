<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191030160047 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-486 (Upload attachment to existing projects)';
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

        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB7E3C61F9');
        $this->addSql('DROP INDEX IDX_795FD9BB4FCC0FB9 ON attachment');
        $this->addSql('ALTER TABLE attachment DROP id_client_owner, CHANGE id_type id_type INT DEFAULT NULL, CHANGE original_name original_name VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE attachment CHANGE archived archived_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
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

        $this->addSql('ALTER TABLE attachment ADD id_client_owner INT DEFAULT NULL, CHANGE id_type id_type INT NOT NULL, CHANGE original_name original_name VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB7E3C61F9 FOREIGN KEY (id_client_owner) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_795FD9BB4FCC0FB9 ON attachment (id_client_owner)');
        $this->addSql('ALTER TABLE attachment CHANGE archived_at archived DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
