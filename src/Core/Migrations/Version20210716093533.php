<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210716093533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Core]: CALS-3311 add hubspot company table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE core_hubspot_company (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, company_id INT NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_597B016EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE core_hubspot_company ADD CONSTRAINT FK_597B016EA76ED395 FOREIGN KEY (user_id) REFERENCES core_user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE core_hubspot_company');
    }
}
