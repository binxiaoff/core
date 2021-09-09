<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210217002242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-3320 Add archivingDate to term';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_term ADD archiving_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_term DROP archiving_date');
    }
}
