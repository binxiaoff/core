<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210219175109 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return '[Agency] CALS-3222 Add borrowerInput to Answer';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_term_answer ADD borrower_input VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_term_answer DROP borrower_input');
    }
}
