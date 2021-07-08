<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210706141119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Core] CALS-3331 remove unused entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE syndication_project_tag DROP FOREIGN KEY FK_7F469F89BAD26311');
        $this->addSql('DROP TABLE syndication_project_tag');
        $this->addSql('DROP TABLE syndication_tag');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE syndication_project_tag (project_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_7F469F89166D1F9C (project_id), INDEX IDX_7F469F89BAD26311 (tag_id), PRIMARY KEY(project_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE syndication_tag (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE syndication_project_tag ADD CONSTRAINT FK_7F469F89166D1F9C FOREIGN KEY (project_id) REFERENCES syndication_project (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE syndication_project_tag ADD CONSTRAINT FK_7F469F89BAD26311 FOREIGN KEY (tag_id) REFERENCES syndication_tag (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
