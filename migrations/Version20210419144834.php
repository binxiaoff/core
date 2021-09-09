<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210419144834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3079 ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE folder_file TO core_folder_file');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE core_folder_file TO folder_file');
    }
}
