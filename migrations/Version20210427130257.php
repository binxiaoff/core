<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210427130257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3695 create credit_guaranty_staff_permission table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE credit_guaranty_staff_permission (id INT AUTO_INCREMENT NOT NULL, id_staff INT NOT NULL, public_id VARCHAR(36) NOT NULL, permissions INT NOT NULL COMMENT \'(DC2Type:bitmask)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_ED295656B5B48B91 (public_id), INDEX IDX_ED295656ACEBB2A2 (id_staff), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_staff_permission ADD CONSTRAINT FK_ED295656ACEBB2A2 FOREIGN KEY (id_staff) REFERENCES core_staff (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program DROP FOREIGN KEY FK_190C774F699B6BAF');
        $this->addSql('ALTER TABLE credit_guaranty_program ADD id_managing_company INT NOT NULL');
        $this->addSql(<<<'SQL'
                    UPDATE credit_guaranty_program p 
                    INNER JOIN core_staff cs ON p.added_by = cs.id
                    LEFT JOIN core_team_edge cte ON cte.id_descendent = cs.id_team
                    LEFT JOIN core_company cc ON cc.id_root_team = COALESCE(cte.id_ancestor, cs.id_team)
                    SET p.id_managing_company = cc.id 
                    WHERE cc.id IS NOT NULL
            SQL);
        $this->addSql('UPDATE credit_guaranty_program p INNER JOIN core_staff cs ON p.added_by = cs.id SET p.added_by = cs.id_user WHERE TRUE');
        $this->addSql('ALTER TABLE credit_guaranty_program ADD CONSTRAINT FK_190C774FD171DF6E FOREIGN KEY (id_managing_company) REFERENCES core_company (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program ADD CONSTRAINT FK_190C774F699B6BAF FOREIGN KEY (added_by) REFERENCES core_user (id)');
        $this->addSql('CREATE INDEX IDX_190C774F8E7654EC ON credit_guaranty_program (id_managing_company)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE credit_guaranty_staff_permission');
        $this->addSql('ALTER TABLE credit_guaranty_program DROP FOREIGN KEY FK_190C774FD171DF6E');
        $this->addSql('ALTER TABLE credit_guaranty_program DROP FOREIGN KEY FK_190C774F699B6BAF');
        $this->addSql('DROP INDEX IDX_190C774F8E7654EC ON credit_guaranty_program');
        $this->addSql('ALTER TABLE credit_guaranty_program DROP id_managing_company');
        $this->addSql('ALTER TABLE credit_guaranty_program ADD CONSTRAINT FK_190C774F699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
