<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210401093429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3264 Add participation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE credit_guaranty_program_participation (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, id_company INT NOT NULL, quota NUMERIC(3, 2) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_41E2C75B5B48B91 (public_id), INDEX IDX_41E2C754C70DEF4 (id_program), INDEX IDX_41E2C759122A03F (id_company), UNIQUE INDEX UNIQ_41E2C759122A03F4C70DEF4 (id_company, id_program), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_program_participation ADD CONSTRAINT FK_41E2C754C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_participation ADD CONSTRAINT FK_41E2C759122A03F FOREIGN KEY (id_company) REFERENCES core_company (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE credit_guaranty_program_participation');
    }
}
