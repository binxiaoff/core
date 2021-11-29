<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211103143522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4484 rename reservation_refusal_date field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE credit_guaranty_field SET field_alias = \'reservation_exclusion_date\' WHERE field_alias = \'reservation_refusal_date\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE credit_guaranty_field SET field_alias = \'reservation_refusal_date\' WHERE field_alias = \'reservation_exclusion_date\'');
    }
}
