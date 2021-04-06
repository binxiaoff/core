<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210406165003 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'CALS-3057 Add missing nullable=false in JoinColumn annotation';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_borrower_member CHANGE id_borrower id_borrower INT NOT NULL');
        $this->addSql('ALTER TABLE agency_participation_member CHANGE id_participation id_participation INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_borrower_member CHANGE id_borrower id_borrower INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation_member CHANGE id_participation id_participation INT DEFAULT NULL');
    }
}
