<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210420180242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2366 CALS-3731 Add archiving date field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_participation ADD archiving_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_participation DROP archiving_date');
    }
}
