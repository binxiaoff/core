<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210222162907 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return '[Agency] CALS-3233 Add field to let agent explict reason for borrower answer refusal';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_term_answer ADD waiver TINYINT(1) NOT NULL, ADD granted_delay INT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_term_answer DROP waiver, DROP granted_delay');
    }
}
