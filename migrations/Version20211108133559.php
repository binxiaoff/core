<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211108133559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4991 add product_category_code property in credit_guaranty_financing_object table + update credit_guaranty_field table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD id_product_category_code INT DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD CONSTRAINT FK_6AECF0F5F7D4C12D FOREIGN KEY (id_product_category_code) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('CREATE INDEX IDX_6AECF0F5F7D4C12D ON credit_guaranty_financing_object (id_product_category_code)');
        $this->addSql("INSERT INTO credit_guaranty_field (public_id, field_alias, tag, category, type, reservation_property_name, property_path, property_type, object_class, comparable, unit, predefined_items) VALUES ('316ba97b-3223-4c72-a147-1ae48a91f7ee', 'product_category_code', 'eligibility', 'loan', 'list', 'financingObjects', 'productCategoryCode', 'ProgramChoiceOption', 'KLS\\\\CreditGuaranty\\\\FEI\\\\Entity\\\\FinancingObject', 1, NULL, NULL)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object DROP FOREIGN KEY FK_6AECF0F5F7D4C12D');
        $this->addSql('DROP INDEX IDX_6AECF0F5F7D4C12D ON credit_guaranty_financing_object');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object DROP id_product_category_code');
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'product_category_code'");
    }
}
