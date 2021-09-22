<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210922132537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Core] CALS-4663 synchronized becomes nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_hubspot_company CHANGE synchronized synchronized DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE core_hubspot_contact CHANGE synchronized synchronized DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_hubspot_company CHANGE synchronized synchronized DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE core_hubspot_contact CHANGE synchronized synchronized DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
