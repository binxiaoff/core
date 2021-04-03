<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210403130248 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return '[Agency] Add unique index on referent and signatory for borrower and participation';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_borrower DROP INDEX IDX_C78A2C4F2B0DC78F, ADD UNIQUE INDEX UNIQ_C78A2C4F2B0DC78F (id_signatory)');
        $this->addSql('ALTER TABLE agency_borrower DROP INDEX IDX_C78A2C4FAE4140F9, ADD UNIQUE INDEX UNIQ_C78A2C4FAE4140F9 (id_referent)');
        $this->addSql('ALTER TABLE agency_participation DROP INDEX IDX_E0ED689EAE4140F9, ADD UNIQUE INDEX UNIQ_E0ED689EAE4140F9 (id_referent)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_borrower DROP INDEX UNIQ_C78A2C4F2B0DC78F, ADD INDEX IDX_C78A2C4F2B0DC78F (id_signatory)');
        $this->addSql('ALTER TABLE agency_borrower DROP INDEX UNIQ_C78A2C4FAE4140F9, ADD INDEX IDX_C78A2C4FAE4140F9 (id_referent)');
        $this->addSql('ALTER TABLE agency_participation DROP INDEX UNIQ_E0ED689EAE4140F9, ADD INDEX IDX_E0ED689EAE4140F9 (id_referent)');
    }
}
