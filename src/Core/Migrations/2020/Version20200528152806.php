<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200528152806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1609 Update dates in project';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
             ALTER TABLE project 
                 ADD interest_expression_deadline DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
                 ADD contractualization_deadline DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
                 RENAME COLUMN lender_consultation_closing_date TO participant_reply_deadline,
                 RENAME COLUMN expected_closing_date TO allocation_deadline,
                 RENAME COLUMN reply_deadline TO signing_deadline
SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
             ALTER TABLE project 
                 DROP interest_expression_deadline,
                 DROP contractualization_deadline,
                 RENAME COLUMN participant_reply_deadline TO lender_consultation_closing_date,
                 RENAME COLUMN allocation_deadline TO expected_closing_date,
                 RENAME COLUMN signing_deadline TO reply_deadline
SQL
        );
    }
}
