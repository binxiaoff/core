<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200313145408 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-550 Add staff status';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_426EF392E173B1B89122A03F ON staff');
        $this->addSql('ALTER TABLE staff ADD active TINYINT(1) DEFAULT \'1\' NOT NULL, ADD archived DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_426EF392E173B1B89122A03F61B169FE ON staff (id_client, id_company, archived)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_426EF392E173B1B89122A03F61B169FE ON staff');
        $this->addSql('ALTER TABLE staff DROP active, DROP archived');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_426EF392E173B1B89122A03F ON staff (id_client, id_company)');
    }
}
