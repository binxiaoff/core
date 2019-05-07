<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190506090119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-104 Add label field to project_attachment_type';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE attachment RENAME INDEX idx_795fd9bb7e3c61f9 TO IDX_795FD9BB21E5A74C');
        $this->addSql('ALTER TABLE attachment RENAME INDEX idx_795fd9bb66ab7494 TO IDX_795FD9BB36B9957C');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE attachment RENAME INDEX idx_795fd9bb36b9957c TO IDX_795FD9BB66AB7494');
        $this->addSql('ALTER TABLE attachment RENAME INDEX idx_795fd9bb21e5a74c TO IDX_795FD9BB7E3C61F9');
        $this->addSql('DROP INDEX UNIQ_4C9C36E6EA750E8 ON project_attachment_type');
        $this->addSql('ALTER TABLE project_attachment_type DROP label');
    }
}
