<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200128140107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-817 Delete old emails';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM mail_queue');
        $this->addSql("DELETE FROM mail_template WHERE name <> 'staff-client-initialisation'");
    }

    public function down(Schema $schema): void
    {
    }
}
