<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190520154800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-133 Add super admin role to client #1';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE clients set roles = \'["ROLE_USER", "ROLE_LENDER", "ROLE_BORROWER", "ROLE_SUPER_ADMIN"]\' WHERE id_client = 1');
        $this->addSql('
            INSERT INTO translations (locale, section, name, translation, added) VALUES
            (\'fr_FR\', \'user-header\', \'logout\', \'Se déconnecter\', NOW()),
            (\'fr_FR\', \'user-header\', \'exit-impersonation\', \'Quitter impersonation\', NOW())
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE clients set roles = \'["ROLE_USER", "ROLE_LENDER", "ROLE_BORROWER"]\' WHERE id_client = 1');
    }
}
