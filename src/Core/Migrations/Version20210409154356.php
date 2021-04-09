<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210409154356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3658 Add configuration for borrower\'s company naf code';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE credit_guaranty_field SET field_alias = 'naf_code_project' WHERE field_alias = 'naf_code'");
        $this->addSql("INSERT INTO credit_guaranty_field (public_id, field_alias, category, type, target_property_access_path, comparable, unit, predefined_items) VALUES ('7cccbd98-6b99-4425-8f29-83a04027740c', 'naf_code_company', 'activity', 'list', '', 0, NULL, NULL)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE credit_guaranty_field SET field_alias = 'naf_code' WHERE field_alias = 'naf_code_project'");
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'naf_code_company'");
    }
}
