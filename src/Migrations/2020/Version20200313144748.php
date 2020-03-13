<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200313144748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1302 Add public id to staff';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE staff ADD public_id VARCHAR(36) NOT NULL');
        $this->addSql('UPDATE staff set public_id = UUID()');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_426EF392B5B48B91 ON staff (public_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_426EF392B5B48B91 ON staff');
        $this->addSql('ALTER TABLE staff DROP public_id');
    }
}
