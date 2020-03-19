<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200319114044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-550 Add tracability to staff status change';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE staff_status (id INT AUTO_INCREMENT NOT NULL, id_staff INT NOT NULL, added_by INT NOT NULL, status SMALLINT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_staff_status_status (status), INDEX idx_staff_status_id_project (id_staff), INDEX idx_staff_status_added_by (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE staff_status ADD CONSTRAINT FK_7E7DD7A7ACEBB2A2 FOREIGN KEY (id_staff) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE staff_status ADD CONSTRAINT FK_7E7DD7A7699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('DROP INDEX UNIQ_426EF392E173B1B89122A03F61B169FE ON staff');
        $this->addSql('DELETE FROM staff WHERE archived IS NOT NULL');
        $this->addSql('ALTER TABLE staff ADD id_current_status INT DEFAULT NULL, DROP active, DROP archived');
        $this->addSql('ALTER TABLE staff ADD CONSTRAINT FK_426EF39241AF0274 FOREIGN KEY (id_current_status) REFERENCES staff_status (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_426EF39241AF0274 ON staff (id_current_status)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_426EF392E173B1B89122A03F ON staff (id_client, id_company)');
        $this->addSql('INSERT INTO staff_status SELECT NULL, id, id, 10, NOW() as added FROM staff');
        $this->addSql('UPDATE staff SET id_current_status = (SELECT id FROM staff_status as status WHERE staff.id = status.id_staff LIMIT 1) WHERE 1 = 1');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE staff DROP FOREIGN KEY FK_426EF39241AF0274');
        $this->addSql('DROP TABLE staff_status');
        $this->addSql('DROP INDEX UNIQ_426EF39241AF0274 ON staff');
        $this->addSql('DROP INDEX UNIQ_426EF392E173B1B89122A03F ON staff');
        $this->addSql('ALTER TABLE staff ADD active TINYINT(1) DEFAULT \'1\' NOT NULL, ADD archived DATETIME DEFAULT NULL, DROP id_current_status');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_426EF392E173B1B89122A03F61B169FE ON staff (id_client, id_company, archived)');
    }
}
