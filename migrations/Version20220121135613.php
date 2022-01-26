<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220121135613 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '[Agency] CALS-5617 Add link to newly created agent bank account rows';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql(<<<SQL
          UPDATE agency_participation apa
          SET apa.id_agent_bank_account = (
            SELECT aaba.id 
            FROM agency_agent_bank_account aaba
            INNER JOIN agency_agent aa ON aaba.id_agent = aa.id
            INNER JOIN agency_project ap ON aa.id_project = ap.id
            INNER JOIN agency_participation_pool app ON ap.id = app.id_project
            WHERE apa.id_participation_pool = app.id
            ORDER BY aaba.id
            LIMIT 1
          )
          WHERE apa.id_agent_bank_account IS NULL
SQL
        );

            $this->addSql(<<<SQL
          UPDATE agency_borrower ab
          SET ab.id_agent_bank_account = (
            SELECT aaba.id
            FROM agency_agent_bank_account aaba
            INNER JOIN agency_agent aa ON aaba.id_agent = aa.id
            INNER JOIN agency_project ap ON aa.id_project = ap.id
            WHERE ab.id_project = ap.id
            ORDER BY aaba.id
            LIMIT 1
          )
          WHERE ab.id_agent_bank_account IS NULL
SQL
);
    }

    public function down(Schema $schema) : void
    {
        $this->skipIf(true, 'Data only migration');
    }
}
