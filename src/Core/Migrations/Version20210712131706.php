<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210712131706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-3888 create credit_guaranty_financing_object_unblocking table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE credit_guaranty_financing_object_unblocking (
            id INT AUTO_INCREMENT NOT NULL,
            id_financing_object INT NOT NULL,
            public_id VARCHAR(36) NOT NULL,
            unblocking_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\',
            added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            invoice_money_amount NUMERIC(15, 2) NOT NULL,
            invoice_money_currency VARCHAR(3) NOT NULL,
            achievement_money_amount NUMERIC(15, 2) NOT NULL,
            achievement_money_currency VARCHAR(3) NOT NULL,
            total_money_amount NUMERIC(15, 2) NOT NULL,
            total_money_currency VARCHAR(3) NOT NULL,
            UNIQUE INDEX UNIQ_B9006E5B5B48B91 (public_id),
            INDEX IDX_B9006E562547109 (id_financing_object),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object_unblocking ADD CONSTRAINT FK_B9006E562547109 FOREIGN KEY (id_financing_object) REFERENCES credit_guaranty_financing_object (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE credit_guaranty_financing_object_unblocking');
    }
}
