<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210216152419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3321 Add Answer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agency_term_answer (id INT AUTO_INCREMENT NOT NULL, id_term INT DEFAULT NULL, id_document INT DEFAULT NULL, validation TINYINT(1) DEFAULT NULL, validation_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', borrower_comment LONGTEXT DEFAULT NULL, agent_comment LONGTEXT DEFAULT NULL, public_id VARCHAR(36) NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_19E4CF9B5B48B91 (public_id), INDEX IDX_19E4CF92E2FFB8F (id_term), INDEX IDX_19E4CF988B266E3 (id_document), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_term_answer ADD CONSTRAINT FK_19E4CF92E2FFB8F FOREIGN KEY (id_term) REFERENCES agency_term (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_term_answer ADD CONSTRAINT FK_19E4CF988B266E3 FOREIGN KEY (id_document) REFERENCES core_file (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE agency_term_answer');
    }
}
