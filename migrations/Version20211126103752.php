<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211126103752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-5191 replace tax_number and siret properties with registration_number in credit_guaranty_borrower table + update credit_guaranty_field table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD registration_number VARCHAR(200) DEFAULT NULL, DROP tax_number, DROP siret');

        $fieldsQuery = 'SELECT cgf.id FROM credit_guaranty_field cgf WHERE cgf.field_alias IN ("tax_number", "siret")';
        $this->addSql('DELETE FROM credit_guaranty_program_eligibility_condition WHERE id_left_operand_field IN (' . $fieldsQuery . ') OR id_right_operand_field IN (' . $fieldsQuery . ')');
        $this->addSql('DELETE FROM credit_guaranty_program_eligibility_configuration WHERE id_program_eligibility IN (SELECT cgpe.id FROM credit_guaranty_program_eligibility cgpe WHERE id_field IN ('. $fieldsQuery . '))');
        $this->addSql('DELETE FROM credit_guaranty_program_eligibility WHERE id_field IN ('. $fieldsQuery . ')');
        $this->addSql('DELETE FROM credit_guaranty_reporting_template_field WHERE id_field IN ('. $fieldsQuery . ')');
        $this->addSql('DELETE FROM credit_guaranty_field WHERE field_alias IN ("tax_number", "siret")');
        $this->addSql("INSERT INTO credit_guaranty_field (public_id, field_alias, tag, category, type, reservation_property_name, property_path, property_type, object_class, comparable, unit, predefined_items) VALUES ('55e19a28-d533-49c2-aaf7-820f22df8268', 'registration_number', 'eligibility', 'profile', 'other', 'borrower', 'registrationNumber', 'string', 'KLS\\\\CreditGuaranty\\\\FEI\\\\Entity\\\\Borrower', 0, NULL, NULL)");
        // no need to migrate data (cf CALS-5191 comments)
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD tax_number VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD siret VARCHAR(14) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP registration_number');
        $this->addSql('DELETE FROM credit_guaranty_field WHERE field_alias = "registration_number"');
        $this->addSql("INSERT INTO credit_guaranty_field (public_id, field_alias, tag, category, type, reservation_property_name, property_path, property_type, object_class, comparable, unit, predefined_items) VALUES ('f6ea8c30-48d1-4852-9c4a-5e1298f7f902', 'siret', 'eligibility', 'profile', 'other', 'borrower', 'registrationNumber', 'siret', 'KLS\\\\CreditGuaranty\\\\FEI\\\\Entity\\\\Borrower', 0, NULL, NULL)");
        $this->addSql("INSERT INTO credit_guaranty_field (public_id, field_alias, tag, category, type, reservation_property_name, property_path, property_type, object_class, comparable, unit, predefined_items) VALUES ('093a2142-ab5d-4b57-afb0-e8749131740b', 'tax_number', 'eligibility', 'profile', 'other', 'borrower', 'registrationNumber', 'taxNumber', 'KLS\\\\CreditGuaranty\\\\FEI\\\\Entity\\\\Borrower', 0, NULL, NULL)");
    }
}
