<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210806135420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] rename Unilend namespace to KLS in credit_guaranty_field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE credit_guaranty_field SET object_class = 'KLS\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower'");
        $this->addSql("UPDATE credit_guaranty_field SET object_class = 'KLS\\\\CreditGuaranty\\\\Entity\\\\Project' WHERE object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Project'");
        $this->addSql("UPDATE credit_guaranty_field SET object_class = 'KLS\\\\CreditGuaranty\\\\Entity\\\\FinancingObject' WHERE object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\FinancingObject'");
    }
}
