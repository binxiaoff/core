<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210916125306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4447 create template field table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE credit_guaranty_reporting_template_field (id INT AUTO_INCREMENT NOT NULL, id_reporting_template INT NOT NULL, id_field INT NOT NULL, position SMALLINT NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_89D582B3B5B48B91 (public_id), INDEX IDX_89D582B3CC99D7D5 (id_reporting_template), INDEX IDX_89D582B3B5700468 (id_field), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_reporting_template_field ADD CONSTRAINT FK_89D582B3CC99D7D5 FOREIGN KEY (id_reporting_template) REFERENCES credit_guaranty_reporting_template (id)');
        $this->addSql('ALTER TABLE credit_guaranty_reporting_template_field ADD CONSTRAINT FK_89D582B3B5700468 FOREIGN KEY (id_field) REFERENCES credit_guaranty_field (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE credit_guaranty_reporting_template_field');
    }
}
