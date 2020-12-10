<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Ramsey\Uuid\Uuid;
use Unilend\Core\Entity\CompanyModule;

final class Version20201015083351 extends AbstractMigration
{
    public const BANKS = [
        'CDM' => [
            'name' => 'Crédit du Maroc',
            'bankCode' => 11778,
        ],
        'FONC' => [
            'name' => 'Foncaris',
            'bankCode' => 28860,
        ],
        'CASA' => [
            'name' => 'Crédit Agricole S.A.',
            'bankCode' => 30006,
        ],
        'LIXX' => [
            'name' => 'Lixxbail',
            'bankCode' => 13150,
        ],
        'CHAL' => [
            'name' => 'Banque Chalus' ,
            'bankCode' => 10188,
        ],
    ];

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-2693 Remove some companies';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        foreach (self::BANKS as $shortCode => ['name' => $name, 'bankCode' => $bankCode]) {
            $this->addSql(<<<SQL
                DELETE company_module_log 
                FROM company_module_log 
                INNER JOIN company_module cm on company_module_log.id_module = cm.id 
                INNER JOIN company c on cm.id_company = c.id 
                WHERE short_code = '$shortCode'
SQL
            );
            $this->addSql(<<<SQL
                DELETE cm 
                FROM company_module cm
                INNER JOIN company c on cm.id_company = c.id  
                WHERE short_code = '$shortCode'
SQL
            );
            $this->addSql(<<<SQL
                UPDATE company
                SET id_current_status = NULL
                WHERE short_code = '$shortCode'
SQL);

            $this->addSql(<<<SQL
                DELETE cs
                FROM company_status cs
                INNER JOIN company c on cs.id_company = c.id  
                WHERE short_code = '$shortCode'
SQL
            );
            
            $this->addSql(<<<SQL
                DELETE staff
                FROM staff 
                INNER JOIN company c on staff.id_company = c.id
                WHERE short_code = '$shortCode'
SQL);
            $this->addSql(<<<SQL
                DELETE project_participation
                FROM project_participation 
                INNER JOIN company c on project_participation.id_company = c.id 
                WHERE short_code = '$shortCode'
SQL);

            $this->addSql(<<<SQL
                DELETE project
                FROM project
                INNER JOIN company c on project.id_company_submitter = c.id
                WHERE c.short_code = '$shortCode'
SQL);

            $this->addSql(<<<SQL
                DELETE company
                FROM company
                WHERE short_code = '$shortCode'
SQL);
        }
    }

    /**
     * @param Schema $schema
     *
     * @throws Exception
     */
    public function down(Schema $schema): void
    {
        $modules = ['arrangement', 'participation', 'agency', 'arrangement_external_bank'];

        foreach (self::BANKS as $shortCode => ['name' => $name, 'bankCode' => $bankCode]) {
            $companyPublicId = (string) (Uuid::uuid4());

            $this->addSql(<<<SQL
INSERT INTO company(display_name, company_name, added, short_code, public_id, group_name, applicable_vat, bank_code) 
VALUES ('$name', '$name', NOW(), '$shortCode', '{$companyPublicId}', 'Crédit Agricole', 'metropolitan', '$bankCode')
SQL
            );

            $statusPublicId = (string) (Uuid::uuid4());
            $this->addSql("INSERT INTO company_status SELECT NULL as id, (SELECT MAX(id) from company) as id_company, 0 as status, NOW() as added, '$statusPublicId' as public_id");
            $this->addSql("UPDATE company SET id_current_status = (SELECT MAX(id) from company_status) WHERE public_id = '$companyPublicId'");

            foreach ($modules as $module) {
                $modulePublicId = (string) (Uuid::uuid4());
                $this->addSql(<<<SQL
INSERT INTO company_module(id_company, code, activated, public_id ,added) 
SELECT
       (SELECT MAX(id) from company) as id_company,
       '$module' as code,
       0 as activated,
       '$modulePublicId' as public_id,
       NOW() as added
SQL
                );
            }
        }
    }
}
