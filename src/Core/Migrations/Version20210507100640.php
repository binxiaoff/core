<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210507100640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Core] Add name for File';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_file ADD name VARCHAR(191) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_file DROP name');
    }
}
