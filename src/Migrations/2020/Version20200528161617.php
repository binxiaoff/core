<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200528161617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1610 Add interest expression field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD interest_expression TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP interest_expression');
    }
}
