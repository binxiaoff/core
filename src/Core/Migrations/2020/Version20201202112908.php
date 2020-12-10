<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201202112908 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add error_message to mail_queue';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE mail_queue ADD error_message TEXT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE mail_queue DROP error_message');
    }
}
