<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220110150556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4634 Add table to store exported reporting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE credit_guaranty_reporting (id INT AUTO_INCREMENT NOT NULL, id_file INT NOT NULL, id_reporting_temmplate INT NOT NULL, added_by INT NOT NULL, filters JSON DEFAULT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_95727FFBB5B48B91 (public_id), UNIQUE INDEX UNIQ_95727FFB7BF2A12 (id_file), INDEX IDX_95727FFBB8A93446 (id_reporting_temmplate), INDEX IDX_95727FFB699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_reporting ADD CONSTRAINT FK_95727FFB7BF2A12 FOREIGN KEY (id_file) REFERENCES core_file (id)');
        $this->addSql('ALTER TABLE credit_guaranty_reporting ADD CONSTRAINT FK_95727FFBB8A93446 FOREIGN KEY (id_reporting_temmplate) REFERENCES credit_guaranty_reporting_template (id)');
        $this->addSql('ALTER TABLE credit_guaranty_reporting ADD CONSTRAINT FK_95727FFB699B6BAF FOREIGN KEY (added_by) REFERENCES core_user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE credit_guaranty_reporting');
    }
}
