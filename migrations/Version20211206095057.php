<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211206095057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-5317 add new nullable boolean type properties in credit_guaranty_borrower + update credit_guaranty_field table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower
            ADD economically_viable TINYINT(1) DEFAULT NULL,
            ADD benefiting_profit_transfer TINYINT(1) DEFAULT NULL,
            ADD listed_on_stock_market TINYINT(1) DEFAULT NULL,
            ADD in_non_cooperative_jurisdiction TINYINT(1) DEFAULT NULL,
            ADD subject_of_unperformed_recovery_order TINYINT(1) DEFAULT NULL,
            ADD subject_of_restructuring_plan TINYINT(1) DEFAULT NULL,
            ADD project_received_feaga_ocm_funding TINYINT(1) DEFAULT NULL,
            ADD loan_supporting_documents_dates_after_application TINYINT(1) DEFAULT NULL,
            ADD loan_allowed_refinance_restructure TINYINT(1) DEFAULT NULL,
            ADD transaction_affected TINYINT(1) DEFAULT NULL
        ');

        $fields = <<<'INSERT_FIELDS'
            INSERT INTO credit_guaranty_field (public_id, tag, category, type, field_alias, reservation_property_name, property_path, property_type, object_class, comparable, unit, predefined_items) VALUES
            ('ac3be9c4-c845-4a4b-b4c7-2feed75b44d7', 'eligibility', 'profile', 'bool', 'economically_viable', 'borrower', 'economicallyViable', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 1, NULL, NULL),
            ('7e16ca3f-604e-41db-92a9-2402dd016772', 'eligibility', 'profile', 'bool', 'loan_supporting_documents_dates_after_application', 'borrower', 'loanSupportingDocumentsDatesAfterApplication', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 1, NULL, NULL),
            ('8349e76f-2ad3-40cf-b900-d88c7e252044', 'eligibility', 'profile', 'bool', 'benefiting_profit_transfer', 'borrower', 'benefitingProfitTransfer', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 1, NULL, NULL),
            ('05575342-ec82-4305-989b-116bb3057032', 'eligibility', 'profile', 'bool', 'loan_allowed_refinance_restructure', 'borrower', 'loanAllowedRefinanceRestructure', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 1, NULL, NULL),
            ('5ee963ec-23a3-4cc2-9603-f9a29b775926', 'eligibility', 'profile', 'bool', 'project_received_feaga_ocm_funding', 'borrower', 'projectReceivedFeagaOcmFunding', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 1, NULL, NULL),
            ('7aaee610-5fdd-424f-9810-538092d98d40', 'eligibility', 'profile', 'bool', 'listed_on_stock_market', 'borrower', 'listedOnStockMarket', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 1, NULL, NULL),
            ('1f46fbaa-bda6-42df-8477-4bb33458c6a7', 'eligibility', 'profile', 'bool', 'subject_of_unperformed_recovery_order', 'borrower', 'subjectOfUnperformedRecoveryOrder', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 1, NULL, NULL),
            ('021f4a70-bed9-4b67-b4f1-36b06f1c44b0', 'eligibility', 'profile', 'bool', 'subject_of_restructuring_plan', 'borrower', 'subjectOfRestructuringPlan', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 1, NULL, NULL),
            ('794a4c46-83be-494c-8982-4665e2ce7489', 'eligibility', 'profile', 'bool', 'in_non_cooperative_jurisdiction', 'borrower', 'inNonCooperativeJurisdiction', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 1, NULL, NULL),
            ('8fd62489-0571-4bad-b968-880daf6a8516', 'eligibility', 'profile', 'bool', 'transaction_affected', 'borrower', 'transactionAffected', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 1, NULL, NULL);
            INSERT_FIELDS;
        $this->addSql($fields);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower  DROP economically_viable, DROP benefiting_profit_transfer, DROP listed_on_stock_market, DROP in_non_cooperative_jurisdiction, DROP subject_of_unperformed_recovery_order, DROP subject_of_restructuring_plan, DROP project_received_feaga_ocm_funding, DROP loan_supporting_documents_dates_after_application, DROP loan_allowed_refinance_restructure, DROP transaction_affected');
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias IN ('economically_viable', 'loan_supporting_documents_dates_after_application', 'benefiting_profit_transfer', 'loan_allowed_refinance_restructure', 'project_received_feaga_ocm_funding', 'listed_on_stock_market', 'subject_of_unperformed_recovery_order', 'subject_of_restructuring_plan', 'in_non_cooperative_jurisdiction', 'transaction_affected')");
    }
}
