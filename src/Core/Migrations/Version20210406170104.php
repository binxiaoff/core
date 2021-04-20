<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210406170104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3057 Remove useless updated field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower_member DROP updated');
        $this->addSql('ALTER TABLE agency_participation_member DROP updated');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower_member ADD updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE agency_participation_member ADD updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
