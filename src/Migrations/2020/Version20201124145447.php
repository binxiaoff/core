<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201124145447 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2691 Remove unused fields';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE client_status DROP content');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE client_status ADD content MEDIUMTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
