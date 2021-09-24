<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210205094441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3317 Remove companyGroupTag from teams';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE core_team_company_group_tag');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE core_team_company_group_tag (team_id INT NOT NULL, company_group_tag_id INT NOT NULL, INDEX IDX_A7558624296CD8AE (team_id), INDEX IDX_A75586249799710F (company_group_tag_id), PRIMARY KEY(team_id, company_group_tag_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE core_team_company_group_tag ADD CONSTRAINT FK_FFDE5B56296CD8AE FOREIGN KEY (team_id) REFERENCES core_team (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE core_team_company_group_tag ADD CONSTRAINT FK_FFDE5B569799710F FOREIGN KEY (company_group_tag_id) REFERENCES core_company_group_tag (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
