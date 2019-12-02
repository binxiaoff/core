<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\{DBALException, Schema\Schema};
use Doctrine\Migrations\AbstractMigration;

final class Version20191202094433 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'FRONT-38 add public id to legal_document';
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

        $this->addSql('ALTER TABLE legal_document ADD public_id VARCHAR(36) NOT NULL');
        $this->addSql('UPDATE legal_document SET public_id = "3ac531f2-14e9-11ea-8b64-0226455cbcaf" WHERE id = 1');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_72A4FDB7B5B48B91 ON legal_document (public_id)');
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

        $this->addSql('DROP INDEX UNIQ_72A4FDB7B5B48B91 ON legal_document');
        $this->addSql('ALTER TABLE legal_document DROP public_id');
    }
}
