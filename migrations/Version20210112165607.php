<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210112165607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2112 Add syndication modality field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE agency_project 
                ADD silent_syndication TINYINT(1) NOT NULL, 
                ADD principal_syndication_type VARCHAR(30) DEFAULT NULL, 
                ADD principal_participation_type VARCHAR(30) DEFAULT NULL, 
                ADD principal_risk_type VARCHAR(30) DEFAULT NULL, 
                ADD secondary_syndication_type VARCHAR(30) DEFAULT NULL, 
                ADD secondary_participation_type VARCHAR(30) DEFAULT NULL, 
                ADD secondary_risk_type VARCHAR(30) DEFAULT NULL
            SQL
);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE agency_project 
                DROP silent_syndication, 
                DROP principal_syndication_type, 
                DROP principal_participation_type, 
                DROP principal_risk_type, 
                DROP secondary_syndication_type, 
                DROP secondary_participation_type, 
                DROP secondary_risk_type
            SQL
);
    }
}
