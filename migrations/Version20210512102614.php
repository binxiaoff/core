<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210512102614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3413 [Core] remove unique index on nace_code';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_B74A64DBBF3D7168609C91B2 ON core_naf_nace');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B74A64DBBF3D7168 ON core_naf_nace (naf_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_B74A64DBBF3D7168 ON core_naf_nace');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B74A64DBBF3D7168609C91B2 ON core_naf_nace (naf_code, nace_code)');
    }
}
