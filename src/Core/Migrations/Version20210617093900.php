<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210617093900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3949 Update KLS/CALS short code';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE core_company SET short_code = "KLS", display_name = "KLS", company_name = "KLS" WHERE short_code = "CALS"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE core_company SET short_code = "CALS", display_name = "CA Lending Services", company_name = "Crédit Agricole Lending Services" WHERE short_code = "KLS"');
    }
}
