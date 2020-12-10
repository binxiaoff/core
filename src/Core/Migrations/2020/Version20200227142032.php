<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200227142032 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1215 Allow multiple staff for client';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE staff DROP INDEX UNIQ_426EF392E173B1B8, ADD INDEX IDX_426EF392E173B1B8 (id_client)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_426EF392E173B1B89122A03F ON staff (id_client, id_company)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE staff DROP INDEX IDX_426EF392E173B1B8, ADD UNIQUE INDEX UNIQ_426EF392E173B1B8 (id_client)');
        $this->addSql('DROP INDEX UNIQ_426EF392E173B1B89122A03F ON staff');
    }
}
