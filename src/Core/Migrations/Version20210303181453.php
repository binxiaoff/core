<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210303181453 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return '[Agency] CALS-3494 Update term answer to add field to handle irregularities';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_term_answer ADD breach TINYINT(1) NOT NULL, ADD breach_comment LONGTEXT DEFAULT NULL, ADD waiver_comment LONGTEXT DEFAULT NULL, CHANGE waiver waiver TINYINT(1) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_term_answer DROP breach, DROP breach_comment, DROP waiver_comment, CHANGE waiver waiver TINYINT(1) NOT NULL');
    }
}
