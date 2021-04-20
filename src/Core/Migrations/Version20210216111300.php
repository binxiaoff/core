<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210216111300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3302 Add sharing date to Term';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_term ADD sharing_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_term DROP sharing_date');
    }
}
