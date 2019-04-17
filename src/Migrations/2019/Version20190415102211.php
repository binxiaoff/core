<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190415102211 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Configure products';
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
        $this->addSql('ALTER TABLE product CHANGE updated updated DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_1FEE6150EA750E8 ON repayment_type');
        $this->addSql('ALTER TABLE repayment_type ADD periodicity INT NOT NULL AFTER label, CHANGE updated updated DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1FEE6150EA750E8C53CC5BC ON repayment_type (label, periodicity)');
        $this->addSql('TRUNCATE repayment_type');
        $this->addSql(
            <<<'REPAYMENTTYPE'
INSERT INTO repayment_type (label, periodicity, added)
VALUES
  ("fixed_capital", 1, NOW()),
  ("fixed_capital", 3, NOW()),
  ("fixed_capital", 6, NOW()),
  ("fixed_capital", 12, NOW()),
  ("fixed_payment", 1, NOW()),
  ("fixed_payment", 3, NOW()),
  ("fixed_payment", 6, NOW()),
  ("fixed_payment", 12, NOW()),
  ("deferred", 1, NOW()),
  ("deferred", 3, NOW()),
  ("deferred", 6, NOW()),
  ("deferred", 12, NOW()),
  ("in_fine", 1, NOW()),
  ("in_fine", 3, NOW()),
  ("in_fine", 6, NOW()),
  ("in_fine", 12, NOW())
REPAYMENTTYPE
        );
        $this->addSql('TRUNCATE product_attribute'); // Should have been done before - no rollback necessary
        $this->addSql('ALTER TABLE product CHANGE proxy_template proxy_template VARCHAR(191) DEFAULT NULL, CHANGE proxy_block_slug proxy_block_slug VARCHAR(191) DEFAULT NULL');
        $this->addSql(
            <<<'PRODUCT'
INSERT INTO product (label, id_repayment_type, status, added)
VALUES
  ("fixed_capital_monthly", (SELECT id_repayment_type FROM repayment_type WHERE label = "fixed_capital" AND periodicity = 1), 1, NOW()),
  ("fixed_capital_quarterly", (SELECT id_repayment_type FROM repayment_type WHERE label = "fixed_capital" AND periodicity = 3), 1, NOW()),
  ("fixed_capital_bi_annual", (SELECT id_repayment_type FROM repayment_type WHERE label = "fixed_capital" AND periodicity = 6), 1, NOW()),
  ("fixed_capital_annual", (SELECT id_repayment_type FROM repayment_type WHERE label = "fixed_capital" AND periodicity = 12), 1, NOW()),
  ("fixed_payment_monthly", (SELECT id_repayment_type FROM repayment_type WHERE label = "fixed_payment" AND periodicity = 1), 1, NOW()),
  ("fixed_payment_quarterly", (SELECT id_repayment_type FROM repayment_type WHERE label = "fixed_payment" AND periodicity = 3), 1, NOW()),
  ("fixed_payment_bi_annual", (SELECT id_repayment_type FROM repayment_type WHERE label = "fixed_payment" AND periodicity = 6), 1, NOW()),
  ("fixed_payment_annual", (SELECT id_repayment_type FROM repayment_type WHERE label = "fixed_payment" AND periodicity = 12), 1, NOW()),
  ("deferred_monthly", (SELECT id_repayment_type FROM repayment_type WHERE label = "deferred" AND periodicity = 1), 1, NOW()),
  ("deferred_quarterly", (SELECT id_repayment_type FROM repayment_type WHERE label = "deferred" AND periodicity = 3), 1, NOW()),
  ("deferred_bi_annual", (SELECT id_repayment_type FROM repayment_type WHERE label = "deferred" AND periodicity = 6), 1, NOW()),
  ("deferred_annual", (SELECT id_repayment_type FROM repayment_type WHERE label = "deferred" AND periodicity = 12), 1, NOW()),
  ("in_fine_monthly", (SELECT id_repayment_type FROM repayment_type WHERE label = "in_fine" AND periodicity = 1), 1, NOW()),
  ("in_fine_quarterly", (SELECT id_repayment_type FROM repayment_type WHERE label = "in_fine" AND periodicity = 3), 1, NOW()),
  ("in_fine_bi_annual", (SELECT id_repayment_type FROM repayment_type WHERE label = "in_fine" AND periodicity = 6), 1, NOW()),
  ("in_fine_annual", (SELECT id_repayment_type FROM repayment_type WHERE label = "in_fine" AND periodicity = 12), 1, NOW())
PRODUCT
        );
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
        $this->addSql(
            <<<'TRANSLATION'
INSERT INTO translations (locale, section, name, translation, added, updated) 
VALUES
  ('fr_FR', 'periodicity', '1-months', 'Mensuel', NOW(), NOW()),
  ('fr_FR', 'periodicity', '3-months', 'Trimestriel', NOW(), NOW()),
  ('fr_FR', 'periodicity', '6-months', 'Semestriel', NOW(), NOW()),
  ('fr_FR', 'periodicity', '12-months', 'Annuel', NOW(), NOW()),
  ('fr_FR', 'amortization-name', 'fixed_capital', 'Capital constant', NOW(), NOW()),
  ('fr_FR', 'amortization-name', 'fixed_payment', 'Échéances constantes', NOW(), NOW()),
  ('fr_FR', 'amortization-name', 'deferred', 'Différé', NOW(), NOW()),
  ('fr_FR', 'amortization-name', 'in_fine', 'In fine', NOW(), NOW())
TRANSLATION
        );
        $this->addSql('UPDATE mail_templates SET content = REPLACE(content, " [EMV DYN]scoringName[EMV /DYN]", ""), compiled_content = REPLACE(compiled_content, " [EMV DYN]scoringName[EMV /DYN]", "")'); // No rollback necessary
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE product CHANGE updated updated DATETIME NOT NULL');
        $this->addSql('DROP INDEX UNIQ_1FEE6150EA750E8C53CC5BC ON repayment_type');
        $this->addSql('ALTER TABLE repayment_type DROP periodicity, CHANGE updated updated DATETIME NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1FEE6150EA750E8 ON repayment_type (label)');
        $this->addSql('INSERT INTO repayment_type (id_repayment_type, label, added, updated) VALUES (1, "amortization_schedule", "2016-09-05 18:10:01", "2016-09-05 18:10:01")');
        $this->addSql(
            <<<'PRODUCT'
ALTER TABLE product
  CHANGE proxy_template proxy_template VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci,
  CHANGE proxy_block_slug proxy_block_slug VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci
PRODUCT
        );
        $this->addSql(
            <<<'PRODUCT'
INSERT INTO product (id_product, label, id_repayment_type, status, proxy_template, proxy_block_slug, added, updated) 
VALUES
  (1, 'amortization_linear_monthly', 1, 1, '', '', '2019-03-25 12:05:41', '2019-03-25 12:05:41'),
  (2, 'amortization_linear_trimestrial', 1, 1, '', '', '2019-03-25 12:05:41', '2019-03-25 12:05:41'),
  (3, 'amortization_linear_semestrial', 1, 1, '', '', '2019-03-25 12:05:41', '2019-03-25 12:05:41'),
  (4, 'amortization_linear_annual', 1, 1, '', '', '2019-03-25 12:05:41', '2019-03-25 12:05:41'),
  (5, 'amortization_progressive_monthly', 1, 1, '', '', '2019-03-25 12:05:41', '2019-03-25 12:05:41'),
  (6, 'amortization_progressive_trimestrial', 1, 1, '', '', '2019-03-25 12:05:41', '2019-03-25 12:05:41'),
  (7, 'amortization_progressive_semestrial', 1, 1, '', '', '2019-03-25 12:05:41', '2019-03-25 12:05:41'),
  (8, 'amortization_progressive_annual', 1, 1, '', '', '2019-03-25 12:05:41', '2019-03-25 12:05:41')
PRODUCT
        );
    }
}
