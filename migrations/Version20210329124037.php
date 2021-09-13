<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210329124037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update inequality operator length';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_covenant_rule CHANGE inequality_operator inequality_operator VARCHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE agency_margin_rule CHANGE inequality_operator inequality_operator VARCHAR(3) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_covenant_rule CHANGE inequality_operator inequality_operator VARCHAR(2) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_margin_rule CHANGE inequality_operator inequality_operator VARCHAR(2) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
