<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210623093801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove id_project and id_borrower from reservation. Add id_reservation in project and borrower';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD id_reservation INT NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD CONSTRAINT FK_D7ADB78C5ADA84A2 FOREIGN KEY (id_reservation) REFERENCES credit_guaranty_reservation (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D7ADB78C5ADA84A2 ON credit_guaranty_borrower (id_reservation)');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD id_reservation INT NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D0255ADA84A2 FOREIGN KEY (id_reservation) REFERENCES credit_guaranty_reservation (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A452D0255ADA84A2 ON credit_guaranty_project (id_reservation)');
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP FOREIGN KEY FK_DE8BB8578B4BA121');
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP FOREIGN KEY FK_DE8BB857F12E799E');
        $this->addSql('DROP INDEX UNIQ_DE8BB8578B4BA121 ON credit_guaranty_reservation');
        $this->addSql('DROP INDEX UNIQ_DE8BB857F12E799E ON credit_guaranty_reservation');
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP id_borrower, DROP id_project');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower DROP FOREIGN KEY FK_D7ADB78C5ADA84A2');
        $this->addSql('DROP INDEX UNIQ_D7ADB78C5ADA84A2 ON credit_guaranty_borrower');
        $this->addSql('ALTER TABLE credit_guaranty_borrower DROP id_reservation');
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D0255ADA84A2');
        $this->addSql('DROP INDEX UNIQ_A452D0255ADA84A2 ON credit_guaranty_project');
        $this->addSql('ALTER TABLE credit_guaranty_project DROP id_reservation');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD id_borrower INT NOT NULL, ADD id_project INT DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD CONSTRAINT FK_DE8BB8578B4BA121 FOREIGN KEY (id_borrower) REFERENCES credit_guaranty_borrower (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD CONSTRAINT FK_DE8BB857F12E799E FOREIGN KEY (id_project) REFERENCES credit_guaranty_project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DE8BB8578B4BA121 ON credit_guaranty_reservation (id_borrower)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DE8BB857F12E799E ON credit_guaranty_reservation (id_project)');
    }
}
