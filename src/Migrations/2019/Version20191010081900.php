<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191010081900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-356';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE project_status SET status = 60 WHERE status IN(70,100) ');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(true, 'Old statuses have never been used');
    }
}
