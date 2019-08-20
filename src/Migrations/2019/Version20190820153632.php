<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190820153632 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'TECH-80 Delete Equinoa CMS';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP TABLE blocs');
        $this->addSql('DROP TABLE blocs_elements');
        $this->addSql('DROP TABLE elements');
        $this->addSql('DROP TABLE templates');
        $this->addSql('DROP TABLE tree');
        $this->addSql('DROP TABLE tree_elements');
        $this->addSql('DROP TABLE tree_menu');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE blocs (id_bloc INT AUTO_INCREMENT NOT NULL, name VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, slug VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, status TINYINT(1) NOT NULL, added DATETIME NOT NULL, updated DATETIME DEFAULT NULL, PRIMARY KEY(id_bloc)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE blocs_elements (id INT AUTO_INCREMENT NOT NULL, id_bloc INT NOT NULL, id_element INT NOT NULL, id_langue VARCHAR(2) NOT NULL COLLATE utf8mb4_unicode_ci, value MEDIUMTEXT NOT NULL COLLATE utf8mb4_unicode_ci, complement MEDIUMTEXT NOT NULL COLLATE utf8mb4_unicode_ci, status INT NOT NULL, added DATETIME NOT NULL, updated DATETIME DEFAULT NULL, INDEX id_bloc_2 (id_bloc), INDEX id_element (id_element), UNIQUE INDEX id_bloc (id_bloc, id_element, id_langue), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE elements (id_element INT AUTO_INCREMENT NOT NULL, id_template INT NOT NULL, id_bloc INT NOT NULL, name VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, slug VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, ordre INT DEFAULT 0 NOT NULL, type_element VARCHAR(50) NOT NULL COLLATE utf8mb4_unicode_ci, status SMALLINT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id_element)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE templates (id_template INT AUTO_INCREMENT NOT NULL, name VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, slug VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, added DATETIME NOT NULL, updated DATETIME DEFAULT NULL, PRIMARY KEY(id_template)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tree (id_tree INT AUTO_INCREMENT NOT NULL, id_langue VARCHAR(2) NOT NULL COLLATE utf8mb4_unicode_ci, id_parent INT NOT NULL, id_template INT NOT NULL, id_user INT NOT NULL, title VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, slug VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, img_menu VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, menu_title VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, meta_title VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, meta_description MEDIUMTEXT NOT NULL COLLATE utf8mb4_unicode_ci, meta_keywords MEDIUMTEXT NOT NULL COLLATE utf8mb4_unicode_ci, ordre INT NOT NULL, status INT NOT NULL, status_menu INT NOT NULL, prive INT NOT NULL, indexation INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX id_template (id_template), INDEX id_parent (id_parent), PRIMARY KEY(id_tree)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tree_elements (id INT AUTO_INCREMENT NOT NULL, id_tree INT NOT NULL, id_element INT NOT NULL, id_langue VARCHAR(2) NOT NULL COLLATE utf8mb4_unicode_ci, value LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, complement MEDIUMTEXT NOT NULL COLLATE utf8mb4_unicode_ci, status SMALLINT DEFAULT 0 NOT NULL, added DATETIME NOT NULL, updated DATETIME DEFAULT NULL, UNIQUE INDEX id_tree_2 (id_tree, id_element, id_langue), INDEX id_tree_3 (id_tree), INDEX id_element (id_element), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tree_menu (id INT NOT NULL, id_langue VARCHAR(2) NOT NULL COLLATE utf8mb4_unicode_ci, id_menu INT NOT NULL, nom VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, value VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, complement VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, target VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, ordre INT DEFAULT 0 NOT NULL, status TINYINT(1) NOT NULL, added DATETIME NOT NULL, updated DATETIME DEFAULT NULL, UNIQUE INDEX id_langue (id_langue, id_menu, nom, value, complement), PRIMARY KEY(id, id_langue)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
    }
}
