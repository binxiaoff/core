<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200611130530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix previous projects';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE project SET target_arranger_participation_money_currency = NULL WHERE target_arranger_participation_money_currency = ''");
        $this->addSql("UPDATE project SET target_arranger_participation_money_amount = NULL WHERE target_arranger_participation_money_amount = CAST('' AS DECIMAL)");
        $this->addSql("UPDATE project SET arrangement_commission_money_currency = NULL WHERE arrangement_commission_money_currency = ''");
        $this->addSql("UPDATE project SET arrangement_commission_money_amount = NULL WHERE arrangement_commission_money_amount = CAST('' AS DECIMAL)");
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(true);
    }
}
