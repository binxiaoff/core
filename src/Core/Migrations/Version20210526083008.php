<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210526083008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Add bankAddress on partaker';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent ADD bank_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_borrower ADD bank_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation ADD bank_address VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent DROP bank_address');
        $this->addSql('ALTER TABLE agency_borrower DROP bank_address');
        $this->addSql('ALTER TABLE agency_participation DROP bank_address');
    }
}
