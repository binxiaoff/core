<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190418074207 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Create new contract type';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        $this->addSql('DELETE FROM product WHERE label LIKE "amortization_%"');

        $this->addSql('ALTER TABLE underlying_contract CHANGE block_slug block_slug VARCHAR(191) DEFAULT NULL');
        $this->addSql('INSERT INTO underlying_contract (label, document_template, added) VALUES ("sous_participation", "sous_participation", NOW())');

        $this->addSql('TRUNCATE product_underlying_contract');
        $this->addSql('INSERT INTO product_underlying_contract (id_product, id_contract, added) SELECT id_product, (SELECT id_contract FROM underlying_contract WHERE label = "sous_participation"), NOW() FROM product');

        $this->addSql('ALTER TABLE project_comment DROP FOREIGN KEY FK_26A5E091BB9D5A2');
        $this->addSql('ALTER TABLE project_comment ADD CONSTRAINT FK_26A5E091BB9D5A2 FOREIGN KEY (id_parent) REFERENCES project_comment (id) ON DELETE CASCADE');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        $this->addSql('DELETE FROM underlying_contract WHERE label = "sous_participation"');
        $this->addSql('ALTER TABLE underlying_contract CHANGE block_slug block_slug VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE project_comment DROP FOREIGN KEY FK_26A5E091BB9D5A2');
        $this->addSql('ALTER TABLE project_comment ADD CONSTRAINT FK_26A5E091BB9D5A2 FOREIGN KEY (id_parent) REFERENCES project_comment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }
}
