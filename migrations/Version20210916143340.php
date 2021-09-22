<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210916143340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4653 add first_release_date field in credit_guaranty_financing_object table + drop release_date field from credit_guaranty_financing_object_release';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD first_release_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object_release DROP release_date');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object DROP first_release_date');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object_release ADD release_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }
}
