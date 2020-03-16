<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200304161755 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1228 Adapt BlameableUpdated and BlameableArchichable for multientity';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $tables = [
            'tranche_offer',
            'project_participation_offer',
            'attachment',
        ];

        $this->addSql('SET foreign_key_checks = 0');
        foreach ($tables as $table) {
            $this->addSql("UPDATE {$table} a SET updated_by = (SELECT id FROM staff WHERE a.updated_by = staff.id_client LIMIT 1)");
        }

        $this->addSql('UPDATE attachment a SET archived_by = (SELECT id FROM staff WHERE a.archived_by = staff.id_client LIMIT 1) ');
        $this->addSql('SET foreign_key_checks = 1');

        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC16FE72E1');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC16FE72E1 FOREIGN KEY (updated_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE project_participation_offer DROP FOREIGN KEY FK_1C09098516FE72E1');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C09098516FE72E1 FOREIGN KEY (updated_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB16FE72E1');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB51B07D6D');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB16FE72E1 FOREIGN KEY (updated_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB51B07D6D FOREIGN KEY (archived_by) REFERENCES staff (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $tables = [
            'tranche_offer',
            'project_participation_offer',
            'attachment',
        ];

        $this->addSql('SET foreign_key_checks = 0');
        foreach ($tables as $table) {
            $this->addSql("UPDATE {$table} a SET updated_by = (SELECT id_client FROM staff WHERE a.updated_by = staff.id_client LIMIT 1)");
        }

        $this->addSql('UPDATE attachment a SET archived_by = (SELECT id_client FROM staff WHERE a.archived_by = staff.id_client LIMIT 1) ');
        $this->addSql('SET foreign_key_checks = 1');

        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB51B07D6D');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB16FE72E1');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB51B07D6D FOREIGN KEY (archived_by) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB16FE72E1 FOREIGN KEY (updated_by) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation_offer DROP FOREIGN KEY FK_1C09098516FE72E1');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C09098516FE72E1 FOREIGN KEY (updated_by) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC16FE72E1');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC16FE72E1 FOREIGN KEY (updated_by) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
