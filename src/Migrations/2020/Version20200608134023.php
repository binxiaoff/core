<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200608134023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1537 Archive participation contact';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_participation_contact ADD archived DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_participation_contact DROP archived');
    }
}
