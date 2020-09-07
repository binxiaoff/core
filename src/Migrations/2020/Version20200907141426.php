<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Ramsey\Uuid\Uuid;

final class Version20200907141426 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'CALS-2219 Add external banks module';
    }

    /**
     * @param Schema $schema
     *
     * @throws Exception
     */
    public function up(Schema $schema) : void
    {
        $companyIds = $this->connection->fetchAll('SELECT id FROM company');
        $companyIds = array_column($companyIds, 'id');
        foreach ($companyIds as $companyId) {
            $uuid = (Uuid::uuid4())->toString();
            $this->addSql("INSERT IGNORE INTO company_module VALUES (NULL, '{$companyId}', NULL, 'external_bank', 0, NULL, NOW(), '{$uuid}')");
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql("DELETE FROM company_module WHERE code = 'external_bank'");
    }
}
