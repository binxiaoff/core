<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210604131812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-3851 Augment maximum tranche length';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_tranche CHANGE name name VARCHAR(200) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_tranche CHANGE name name VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
