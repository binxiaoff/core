<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210413094700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3556 Apply DoctrineExtensions changes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE core_team t INNER JOIN core_company c ON t.id = c.id_root_team SET t.name = c.display_name WHERE t.name = "root"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE core_team t INNER JOIN core_company c ON t.id = c.id_root_team SET t.name = "root" WHERE t.name != "root"');
    }
}
