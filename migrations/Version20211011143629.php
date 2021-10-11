<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211011143629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4741 make loan_money field comparable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE credit_guaranty_field SET comparable = 1, unit = 'money' WHERE field_alias = 'loan_money'");
    }
}
