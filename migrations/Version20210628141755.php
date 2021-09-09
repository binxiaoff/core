<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210628141755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3272 Add dataroom drive on program';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program ADD id_drive INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program DROP id_drive');
    }
}
