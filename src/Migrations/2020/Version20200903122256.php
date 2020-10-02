<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

final class Version20200903122256 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2219 Rename companyModule label into code and create missing companyModule lines';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('DROP INDEX UNIQ_31BB425D9122A03FEA750E8 ON company_module');
        $this->addSql('ALTER TABLE company_module CHANGE label code VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_31BB425D9122A03F77153098 ON company_module (id_company, code)');

        $companyIds = $this->connection->fetchAll('SELECT id FROM company');
        $companyIds = array_column($companyIds, 'id');
        foreach (['arrangement', 'participation', 'agency'] as $module) {
            foreach ($companyIds as $companyId) {
                $uuid = (Uuid::uuid4())->toString();
                $this->addSql("INSERT IGNORE INTO company_module(id_company, code, added, public_id, activated) VALUES ({$companyId}, '{$module}', NOW(), '{$uuid}', 0)");
            }
        }
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP INDEX UNIQ_31BB425D9122A03F77153098 ON company_module');
        $this->addSql('ALTER TABLE company_module CHANGE code label VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_31BB425D9122A03FEA750E8 ON company_module (id_company, label)');
    }
}
