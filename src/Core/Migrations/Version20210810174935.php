<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210810174935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] update object_class with FEI';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE credit_guaranty_field SET object_class = 'KLS\\\\CreditGuaranty\\\\FEI\\\\Entity\\\\Borrower' WHERE object_class = 'KLS\\\\CreditGuaranty\\\\Entity\\\\Borrower'");
        $this->addSql("UPDATE credit_guaranty_field SET object_class = 'KLS\\\\CreditGuaranty\\\\FEI\\\\Entity\\\\Project' WHERE object_class = 'KLS\\\\CreditGuaranty\\\\Entity\\\\Project'");
        $this->addSql("UPDATE credit_guaranty_field SET object_class = 'KLS\\\\CreditGuaranty\\\\FEI\\\\Entity\\\\FinancingObject' WHERE object_class = 'KLS\\\\CreditGuaranty\\\\Entity\\\\FinancingObject'");
    }
}
