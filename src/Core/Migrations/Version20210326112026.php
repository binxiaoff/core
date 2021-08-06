<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210326112026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3259 and CALS-3260 Add new fields';
    }

    public function up(Schema $schema): void
    {
        $fields = <<<'INSERT_FIELDS'
            INSERT INTO credit_guaranty_field (public_id, field_alias, category, type, target_property_access_path, comparable, unit, predefined_items) VALUES
            ('23892bef-00b0-4df5-981e-32913e708a2b', 'investment_thematic', 'project', 'list', '', 0, NULL, NULL),
            ('3dbe7d2c-2b78-4f72-ab52-ca3703e39f5b', 'project_total_amount', 'project', 'other', '', 1, 'money', NULL),
            ('a2f8bca2-0662-4569-a334-57bd00972dcc', 'naf_code', 'project', 'list', '', 0, NULL, NULL),
            ('8cb4b512-fa4c-4638-9a28-11d16b450459', 'funding_object', 'project', 'list', '', 0, NULL, NULL),
            ('c15555ab-289a-46d5-8a0c-d4e0e8b8e0d6', 'funding_object_amount', 'project', 'other', '', 1, 'money', NULL),
            ('675056b4-49bb-40a7-bafe-9bcc86ad7b99', 'loan_type', 'loan', 'list', '', 0, NULL, '["term_loan","short_term","revolving_credit","stand_by","signature_commitment"]'),
            ('e9861e5b-0513-4c7b-9799-2f5a7dc267a4', 'loan_duration', 'loan', 'list', '', 0, NULL, null),
            ('f6f933e3-8164-430d-a670-390d0fc48311', 'loan_deferral', 'loan', 'other', '', 1, 'month', NULL),
            ('604ac6ee-86e2-49ab-aac0-bab8b1d20b30', 'loan_released_on_invoice', 'loan', 'bool', '', 0, NULL, NULL);
            INSERT_FIELDS;
        $this->addSql($fields);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM credit_guaranty_field WHERE field_alias in ("investment_thematic", "project_total_amount", "naf_code", "funding_object", "funding_object_amount", "loan_type", "loan_duration", "loan_deferral", "loan_released_on_invoice")');
    }
}
