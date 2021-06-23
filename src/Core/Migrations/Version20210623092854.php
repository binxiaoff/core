<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210623092854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-3982 Remove extraneous spaces from folder names';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE core_folder SET name = TRIM(name)');
    }

    public function down(Schema $schema): void
    {
    }
}
