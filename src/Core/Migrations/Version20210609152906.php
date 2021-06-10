<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210609152906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3920 Update the field alias configurations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'borrower::borrowerNafCode', field_alias = 'borrower_naf_code' WHERE field_alias = 'naf_code_company'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'project::projectNafCode', field_alias = 'project_naf_code' WHERE field_alias = 'naf_code_project'");
    }
}
