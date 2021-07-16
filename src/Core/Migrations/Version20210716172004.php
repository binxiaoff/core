<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210716172004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-4181 Transform contractExtract into long text';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_covenant CHANGE contract_extract contract_extract LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_covenant CHANGE contract_extract contract_extract VARCHAR(5000) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
