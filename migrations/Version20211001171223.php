<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211001171223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-4480 [CreditGuaranty] remove loan_name field + fix project_detail property_path + fix loan_money tag';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'loan_name'");
        $this->addSql("UPDATE credit_guaranty_field SET property_path = 'detail' WHERE field_alias = 'project_detail'");
        $this->addSql("UPDATE credit_guaranty_field SET tag = 'eligibility' WHERE field_alias = 'loan_money'");
    }
}
