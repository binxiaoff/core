<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210107102048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2781 Add Contact entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agency_contact (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, added_by INT NOT NULL, updated_by INT DEFAULT NULL, type VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, department VARCHAR(255) NOT NULL, occupation VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, referent TINYINT(1) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_3A627F69B5B48B91 (public_id), INDEX IDX_3A627F69F12E799E (id_project), INDEX IDX_3A627F69699B6BAF (added_by), INDEX IDX_3A627F6916FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_contact ADD CONSTRAINT FK_3A627F69F12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id)');
        $this->addSql('ALTER TABLE agency_contact ADD CONSTRAINT FK_3A627F69699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id)');
        $this->addSql('ALTER TABLE agency_contact ADD CONSTRAINT FK_3A627F6916FE72E1 FOREIGN KEY (updated_by) REFERENCES core_staff (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE agency_contact');
    }
}
