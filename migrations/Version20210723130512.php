<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210723130512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Syndication] CALS-3013 remove old messagerie';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE syndication_project_message');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE syndication_project_message (id INT AUTO_INCREMENT NOT NULL, id_participation INT NOT NULL, added_by INT NOT NULL, content LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, archived DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', public_id VARCHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_FA8F31D5157D332A (id_participation), INDEX IDX_FA8F31D5699B6BAF (added_by), UNIQUE INDEX UNIQ_FA8F31D5B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE syndication_project_message ADD CONSTRAINT FK_FA8F31D5157D332A FOREIGN KEY (id_participation) REFERENCES syndication_project_participation (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE syndication_project_message ADD CONSTRAINT FK_FA8F31D5699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
