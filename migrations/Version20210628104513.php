<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210628104513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename the id_loan_naf_code FK';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object RENAME INDEX idx_6aecf0f5efe69dfd TO IDX_6AECF0F556535AA6');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object RENAME INDEX idx_6aecf0f556535aa6 TO IDX_6AECF0F5EFE69DFD');
    }
}
