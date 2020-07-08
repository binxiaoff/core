<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200703082807 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1854 Add model for external bank';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company CHANGE name name VARCHAR(300) NOT NULL, CHANGE siren siren VARCHAR(9) DEFAULT NULL, ADD bic VARCHAR(12) NOT NULL, ADD group_name VARCHAR(50) DEFAULT NULL, ADD vat_number VARCHAR(16) DEFAULT NULL, ADD applicable_vat VARCHAR(20) NOT NULL');

        $this->addSql("UPDATE company SET siren = '394157085' WHERE email_domain = 'ca-nord-est.fr'");
        $this->addSql("UPDATE company SET siren = '782989206' WHERE email_domain = 'ca-corse.fr'");
        $this->addSql('UPDATE company SET bic = id');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FBF094FDB8BBA08 ON company (siren)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FBF094FD4962650 ON company (bic)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FBF094F8910B08D ON company (vat_number)');

        $this->addSql("UPDATE company SET applicable_vat = 'metropolitan' WHERE 1 = 1");
        $this->addSql("UPDATE company SET applicable_vat = 'overseas' WHERE short_code IN ('GUAD', 'MART', 'REUN')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_4FBF094FDB8BBA08 ON company');
        $this->addSql('DROP INDEX UNIQ_4FBF094FD4962650 ON company');
        $this->addSql('DROP INDEX UNIQ_4FBF094F8910B08D ON company');
        $this->addSql('ALTER TABLE company DROP bic, DROP group_name, DROP vat_number, DROP applicable_vat');

        $this->addSql('ALTER TABLE company CHANGE siren siren VARCHAR(15) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE company CHANGE name name MEDIUMTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
