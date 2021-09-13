<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210407143243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add project function property to project member classes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower_member ADD project_function VARCHAR(200) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation_member ADD project_function VARCHAR(200) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower_member DROP project_function');
        $this->addSql('ALTER TABLE agency_participation_member DROP project_function');
    }
}
