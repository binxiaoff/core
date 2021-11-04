<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211013090109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4913 update name from financing object';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE credit_guaranty_financing_object SET name=\'name\' where name IS NULL');
    }
}
