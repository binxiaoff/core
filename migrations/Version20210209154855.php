<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210209154855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3298 Add financial covenant rules';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agency_covenant_rule (id INT AUTO_INCREMENT NOT NULL, id_covenant INT DEFAULT NULL, year VARCHAR(255) NOT NULL, expression VARCHAR(255) NOT NULL, public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_926F7788B5B48B91 (public_id), INDEX IDX_926F7788A4306C62 (id_covenant), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_covenant_rule ADD CONSTRAINT FK_926F7788A4306C62 FOREIGN KEY (id_covenant) REFERENCES agency_covenant (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE agency_covenant_rule');
    }
}
