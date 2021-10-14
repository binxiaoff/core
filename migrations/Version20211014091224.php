<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211014091224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4898 set percentage unit for decimal fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE credit_guaranty_field SET unit = \'percentage\' WHERE field_alias = \'aid_intensity\'');
    }
}
