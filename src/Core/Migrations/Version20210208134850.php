<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210208134850 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-3245 Add credit_guaranty_program and credit_guaranty_program_status tables';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE credit_guaranty_program (id INT AUTO_INCREMENT NOT NULL, id_market_segment INT NOT NULL, id_current_status INT DEFAULT NULL, added_by INT NOT NULL, name VARCHAR(100) NOT NULL, description MEDIUMTEXT DEFAULT NULL, distribution_deadline DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', distribution_process JSON DEFAULT NULL, guaranty_duration SMALLINT DEFAULT NULL, guaranty_coverage NUMERIC(4, 4) DEFAULT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', capped_at_amount NUMERIC(15, 2) DEFAULT NULL, capped_at_currency VARCHAR(3) DEFAULT NULL, funds_amount NUMERIC(15, 2) NOT NULL, funds_currency VARCHAR(3) NOT NULL, guaranty_cost_amount NUMERIC(15, 2) DEFAULT NULL, guaranty_cost_currency VARCHAR(3) DEFAULT NULL, UNIQUE INDEX UNIQ_190C774F5E237E06 (name), UNIQUE INDEX UNIQ_190C774FB5B48B91 (public_id), INDEX IDX_190C774F2C71A0E3 (id_market_segment), UNIQUE INDEX UNIQ_190C774F41AF0274 (id_current_status), INDEX IDX_190C774F699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_program_status (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, added_by INT NOT NULL, status SMALLINT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_CEB64F62B5B48B91 (public_id), INDEX IDX_CEB64F624C70DEF4 (id_program), INDEX IDX_CEB64F62699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_program ADD CONSTRAINT FK_190C774F2C71A0E3 FOREIGN KEY (id_market_segment) REFERENCES core_market_segment (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program ADD CONSTRAINT FK_190C774F41AF0274 FOREIGN KEY (id_current_status) REFERENCES credit_guaranty_program_status (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE credit_guaranty_program ADD CONSTRAINT FK_190C774F699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_status ADD CONSTRAINT FK_CEB64F624C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_status ADD CONSTRAINT FK_CEB64F62699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_program_status DROP FOREIGN KEY FK_CEB64F624C70DEF4');
        $this->addSql('ALTER TABLE credit_guaranty_program DROP FOREIGN KEY FK_190C774F41AF0274');
        $this->addSql('DROP TABLE credit_guaranty_program');
        $this->addSql('DROP TABLE credit_guaranty_program_status');
    }
}
