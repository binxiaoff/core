<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201016154250 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2703 Rename interest_request_min_money_amount into interest_request_max_money_amount';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE project_participation CHANGE interest_request_min_money_amount interest_request_max_money_amount NUMERIC(15, 2) DEFAULT NULL, CHANGE interest_request_min_money_currency interest_request_max_money_currency VARCHAR(3) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE project_participation CHANGE interest_request_max_money_amount interest_request_min_money_amount NUMERIC(15, 2) DEFAULT NULL, CHANGE interest_request_max_money_currency interest_request_min_money_currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
