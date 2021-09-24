<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210426085050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Rename Term::breachComment into Term::irregularityComment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_term CHANGE breach_comment irregularity_comment LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_term_history CHANGE breach_comment irregularity_comment LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_term CHANGE irregularity_comment breach_comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_term_history CHANGE irregularity_comment breach_comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
