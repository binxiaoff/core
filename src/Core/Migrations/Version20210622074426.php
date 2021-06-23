<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210622074426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-3884 CALS-3885 update project fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'project_naf_code'");

        $fields = <<<'INSERT_FIELDS'
            INSERT INTO credit_guaranty_field (public_id, category, type, field_alias, reservation_property_name, property_path, object_class, comparable, unit, predefined_items) VALUES
            ('386f841e-3771-4a35-a54a-e4169fd80d63', 'project', 'bool', 'receiving_grant', 'project', 'receivingGrant', 'Unilend\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
            ('be7be094-9b78-4d97-adaa-d08d1edaec67', 'project', 'other', 'investment_street', 'project', 'addressStreet', 'Unilend\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
            ('833b9e3a-c958-4792-adc1-41b05332f965', 'project', 'other', 'investment_post_code', 'project', 'addressPostCode', 'Unilend\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
            ('91fc4bb3-0b1d-4d0d-ba46-54564c30a775', 'project', 'other', 'investment_city', 'project', 'addressCity', 'Unilend\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
            ('c904c2fb-6940-49ef-b9c3-9961c38ef70e', 'project', 'other', 'investment_department', 'project', 'addressDepartment', 'Unilend\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
            ('674d1e2d-cf35-4c05-9ee6-69a5bbe698d6', 'project', 'list', 'investment_country', 'project', 'addressCountry', 'Unilend\\CreditGuaranty\\Entity\\Project', 0, NULL, '["FR"]'),
            ('ee365095-5e4e-4c02-bb45-b71506cbc42b', 'project', 'list', 'investment_type', 'project', 'investmentType', 'Unilend\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
            ('5b621a54-17a8-4226-8251-ef8bc35c0aae', 'project', 'list', 'aid_intensity', 'project', 'aidIntensity', 'Unilend\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
            ('c7b28186-59e6-4032-8abc-144f8c89e6db', 'project', 'list', 'additional_guaranty', 'project', 'additionalGuaranty', 'Unilend\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
            ('d2b8441d-c2ef-4b11-b01e-ed145232995b', 'project', 'list', 'agricultural_branch', 'project', 'agriculturalBranch', 'Unilend\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
            ('58fd1213-d881-494e-abbc-3dfddb672370', 'project', 'other', 'project_contribution', 'project', 'contribution::amount', 'Unilend\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
            ('3a14f624-43c5-4b44-9cec-6128298df493', 'project', 'other', 'eligible_fei_credit', 'project', 'eligibleFeiCredit::amount', 'Unilend\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
            ('a7d8d27a-67d4-4add-90c9-76a787451ca2', 'project', 'other', 'total_fei_credit', 'project', 'totalFeiCredit::amount', 'Unilend\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
            ('29a1d576-44f7-4bf7-8a20-4c354a4206ab', 'project', 'other', 'physical_fei_credit', 'project', 'physicalFeiCredit::amount', 'Unilend\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
            ('838516c5-82a2-40b7-b8f8-d9fc7890f923', 'project', 'other', 'intangible_fei_credit', 'project', 'intangibleFeiCredit::amount', 'Unilend\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
            ('d00e3272-d334-45d2-a93a-1078e6356537', 'project', 'other', 'credit_excluding_fei', 'project', 'creditExcludingFei::amount', 'Unilend\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
            ('ff0d7855-bdfb-448f-b1b5-ef32e4b87c60', 'project', 'other', 'project_grant', 'project', 'grant::amount', 'Unilend\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
            ('fa5dbf8e-ad12-46f4-b2dd-bb12cfbb77ae', 'project', 'other', 'land_value', 'project', 'landValue::amount', 'Unilend\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL);
            INSERT_FIELDS;
        $this->addSql($fields);
    }
}
