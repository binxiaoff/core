<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200629153936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE company_status ADD public_id VARCHAR(36) NOT NULL');

        $result          = $this->connection->executeQuery('SELECT id FROM company_status');
        $companyStatuses = $result->fetchAll(FetchMode::ASSOCIATIVE);

        foreach ($companyStatuses as ['id' => $id]) {
            $uuid = Uuid::uuid4();
            $this->addSql("UPDATE company_status SET public_id = '{$uuid}' WHERE id = {$id}");
        }

        $this->addSql('CREATE UNIQUE INDEX UNIQ_469F0169B5B48B91 ON company_status (public_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_469F0169B5B48B91 ON company_status');
        $this->addSql('ALTER TABLE company_status DROP public_id');
    }
}
