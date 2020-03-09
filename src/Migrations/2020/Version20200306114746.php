<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200306114746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1208';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP COLUMN confidentiality_disclaimer');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD confidentiality_disclaimer LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
