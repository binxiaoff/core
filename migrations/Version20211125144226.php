<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211125144226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-5184 add target_type property in credit_guaranty_borrower table + update credit_guaranty_field table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD id_target_type INT DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD CONSTRAINT FK_D7ADB78C59D48C6F FOREIGN KEY (id_target_type) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('CREATE INDEX IDX_D7ADB78C59D48C6F ON credit_guaranty_borrower (id_target_type)');
        $this->addSql("INSERT INTO credit_guaranty_field (public_id, field_alias, tag, category, type, reservation_property_name, property_path, property_type, object_class, comparable, unit, predefined_items) VALUES ('f5536d91-d870-4012-ba38-34c0c18cc304', 'target_type', 'eligibility', 'profile', 'list', 'borrower', 'targetType', 'ProgramChoiceOption', 'KLS\\\\CreditGuaranty\\\\FEI\\\\Entity\\\\Borrower', 1, NULL, NULL)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower DROP FOREIGN KEY FK_D7ADB78C59D48C6F');
        $this->addSql('DROP INDEX IDX_D7ADB78C59D48C6F ON credit_guaranty_borrower');
        $this->addSql('ALTER TABLE credit_guaranty_borrower DROP id_target_type');
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'target_type'");
    }
}
