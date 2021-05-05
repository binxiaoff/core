<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210210133058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Remove matriculationCity and siren from borrower';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower DROP matriculation_city, DROP siren');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower ADD matriculation_city VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD siren VARCHAR(9) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql("UPDATE agency_borrower SET matriculation_city = REGEXP_SUBSTR(matriculation_number, '[[:alpha:]]+', 4), siren = REGEXP_SUBSTR(matriculation_number, '[[:digit:]]+') WHERE TRUE");
    }
}
