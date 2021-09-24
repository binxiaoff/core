<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210518001816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3817 Make siren non nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_company CHANGE siren siren VARCHAR(9) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_company CHANGE siren siren VARCHAR(9) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
