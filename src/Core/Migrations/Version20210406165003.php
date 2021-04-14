<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210406165003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3057 Add missing nullable=false in JoinColumn annotation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower_member DROP CONSTRAINT FK_5B36A3AA8B4BA121');
        $this->addSql('ALTER TABLE agency_borrower_member CHANGE id_borrower id_borrower INT NOT NULL');
        $this->addSql('ALTER TABLE agency_borrower_member ADD CONSTRAINT FK_5B36A3AA8B4BA121 FOREIGN KEY (id_borrower) REFERENCES agency_borrower (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_participation_member DROP CONSTRAINT FK_D4BCDFFB157D332A');
        $this->addSql('ALTER TABLE agency_participation_member CHANGE id_participation id_participation INT NOT NULL');
        $this->addSql('ALTER TABLE agency_participation_member ADD CONSTRAINT FK_D4BCDFFB157D332A FOREIGN KEY (id_participation) REFERENCES agency_participation (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower_member DROP CONSTRAINT FK_5B36A3AA8B4BA121');
        $this->addSql('ALTER TABLE agency_borrower_member CHANGE id_borrower id_borrower INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_borrower_member ADD CONSTRAINT FK_5B36A3AA8B4BA121 FOREIGN KEY (id_borrower) REFERENCES agency_borrower (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_participation_member DROP CONSTRAINT FK_D4BCDFFB157D332A');
        $this->addSql('ALTER TABLE agency_participation_member CHANGE id_participation id_participation INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation_member ADD CONSTRAINT FK_D4BCDFFB157D332A FOREIGN KEY (id_participation) REFERENCES agency_participation (id) ON DELETE CASCADE');
    }
}
