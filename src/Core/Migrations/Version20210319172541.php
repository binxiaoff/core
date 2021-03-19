<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210319172541 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-3258 Add new field configurations';
    }

    public function up(Schema $schema) : void
    {
        $fields = <<<INSERT_FIELDS
INSERT INTO credit_guaranty_field (public_id, field_alias, category, type, target_property_access_path, comparable, unit, predefined_items) VALUES
('a2bf0ba4-9e56-43f2-94a5-5da47c80cadd', 'siren', 'activity', 'other', '', 0, NULL, NULL),
('f6ea8c30-48d1-4852-9c4a-5e1298f7f902', 'siret', 'activity', 'other', '', 0, NULL, NULL),
('932afe50-582a-462c-b5cc-16cdd3f09c07', 'activity_country', 'activity', 'list', '', 0, NULL, '["FR"]'),
('d61a4e71-4438-46f1-b1a5-376f98566c06', 'activity_start_date', 'activity', 'other', '', 0, NULL, NULL),
('6c067265-5ff5-49f4-84f0-e511a4a7d42e', 'employees_number', 'activity', 'other', '', 1, 'person', NULL),
('9865fb18-ba90-42ce-9455-e7c508acddd9', 'last_year_turnover', 'activity', 'other', '', 1, 'money', NULL),
('a2e2cbad-75d3-42a4-83d9-aef63da4360e', '5_years_average_turnover', 'activity', 'other', '', 1, 'money', NULL),
('938c689e-bddb-42b9-b84a-a00b18523e4f', 'total_assets', 'activity', 'other', '', 1, 'money', NULL),
('5e6b31a1-1007-4e8d-a1ce-9a38e62280b9', 'grant_amount', 'activity', 'other', '', 1, 'money', NULL),
('d5cbbd4c-0101-4d6b-b816-146a1943ee2e', 'low_density_medical_area_exercise', 'activity', 'other', '', 1, 'money', NULL),
('eef6e5ac-8de6-4084-a06b-dd2974141d94', 'legal_form', 'activity', 'list', '', 0, NULL, '["SARL","SAS","SASU","EURL","SA","SELAS"]');
INSERT_FIELDS;
        $this->addSql($fields);
    }
}
