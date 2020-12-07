<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201023080703 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2727 Make interest_expression_enabled nullable';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE project CHANGE interest_expression_enabled interest_expression_enabled TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE project CHANGE interest_expression_enabled interest_expression_enabled TINYINT(1) NOT NULL');
    }
}
