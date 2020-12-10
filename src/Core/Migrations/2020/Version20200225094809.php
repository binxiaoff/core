<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200225094809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1229 Update email replacing arranger with ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE mail_template SET content = REPLACE(content, 'arranger', 'submitterCompany')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE mail_template SET content = REPLACE(content, 'submitterCompany', 'arranger')");
    }
}
