<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210223190200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-3072 Dataroom model';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE abstract_file_container (id INT AUTO_INCREMENT NOT NULL, discr VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE abstract_file_container_file (abstract_file_container_id INT NOT NULL, file_id INT NOT NULL, INDEX IDX_EB7D8B89FD6A21F6 (abstract_file_container_id), UNIQUE INDEX UNIQ_EB7D8B8993CB796C (file_id), PRIMARY KEY(abstract_file_container_id, file_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE core_drive (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE core_folder (id INT NOT NULL, id_drive INT NOT NULL, path LONGTEXT NOT NULL, path_hash VARCHAR(10) NOT NULL, name VARCHAR(50) NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4CFE6A948698B4BF (id_drive), UNIQUE INDEX UNIQ_4CFE6A948698B4BFBCD4EBEF (id_drive, path_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE abstract_file_container_file ADD CONSTRAINT FK_EB7D8B89FD6A21F6 FOREIGN KEY (abstract_file_container_id) REFERENCES abstract_file_container (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE abstract_file_container_file ADD CONSTRAINT FK_EB7D8B8993CB796C FOREIGN KEY (file_id) REFERENCES core_file (id)');
        $this->addSql('ALTER TABLE core_drive ADD CONSTRAINT FK_3E9CD46FBF396750 FOREIGN KEY (id) REFERENCES abstract_file_container (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE core_folder ADD CONSTRAINT FK_4CFE6A948698B4BF FOREIGN KEY (id_drive) REFERENCES core_drive (id)');
        $this->addSql('ALTER TABLE core_folder ADD CONSTRAINT FK_4CFE6A94BF396750 FOREIGN KEY (id) REFERENCES abstract_file_container (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE abstract_file_container_file DROP FOREIGN KEY FK_EB7D8B89FD6A21F6');
        $this->addSql('ALTER TABLE core_drive DROP FOREIGN KEY FK_3E9CD46FBF396750');
        $this->addSql('ALTER TABLE core_folder DROP FOREIGN KEY FK_4CFE6A94BF396750');
        $this->addSql('ALTER TABLE core_folder DROP FOREIGN KEY FK_4CFE6A948698B4BF');
        $this->addSql('DROP TABLE abstract_file_container');
        $this->addSql('DROP TABLE abstract_file_container_file');
        $this->addSql('DROP TABLE core_drive');
        $this->addSql('DROP TABLE core_folder');
    }
}
