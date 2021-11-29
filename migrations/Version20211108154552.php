<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211108154552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4992 set legal_form and loan_type field predefined_items to null';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE credit_guaranty_field SET predefined_items = NULL WHERE field_alias = \'legal_form\'');
        $this->addSql('UPDATE credit_guaranty_field SET predefined_items = NULL WHERE field_alias = \'loan_Type\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE credit_guaranty_field SET predefined_items = \'["SARL","SAS","SASU","EURL","SA","SELAS"]\' WHERE field_alias = \'legal_form\'');
        $this->addSql('UPDATE credit_guaranty_field SET predefined_items = \'["term_loan","short_term","revolving_credit","stand_by","signature_commitment"]\' WHERE field_alias = \'loan_Type\'');
    }
}
