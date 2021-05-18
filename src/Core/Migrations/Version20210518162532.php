<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210518162532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Add anticipatedFinishDate';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project ADD anticipated_finish_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project DROP anticipated_finish_date');
    }
}
