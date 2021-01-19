<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200623143358 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1862 Remove Simplify trancheFee';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tranche ADD commission_type VARCHAR(30) DEFAULT NULL, ADD commission_rate NUMERIC(5, 4) DEFAULT NULL');
        $this->addSql('UPDATE tranche INNER JOIN tranche_fee tf on tranche.id = tf.id_tranche SET commission_type = tf.fee_type, commission_rate = tf.fee_rate WHERE tf.fee_type IS NOT NULL AND tf.fee_rate IS NOT NULL');
        $this->addSql('DROP TABLE tranche_fee');
        $this->addSql('DROP TABLE zz_versioned_tranche_fee');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            CREATE TABLE tranche_fee (
                id INT AUTO_INCREMENT NOT NULL, id_tranche INT NOT NULL, fee_type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
                fee_comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, fee_rate NUMERIC(5, 4) NOT NULL,
                fee_recurring TINYINT(1) NOT NULL,
                updated DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                added DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_ACF46377B8FAF130 (id_tranche),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = '' 
SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE TABLE zz_versioned_tranche_fee (
                id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
                logged_at DATETIME NOT NULL, object_id VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`,
                object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
                version INT NOT NULL,
                data LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT '(DC2Type:array)',
                username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`,
                INDEX IDX_EB30A0F0232D562B69684D7DBF1CD3C3 (object_id, object_class, version),
                INDEX IDX_EB30A0F0A78D87A7 (logged_at),
                INDEX IDX_EB30A0F0F85E0677 (username),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = '' 
SQL
        );
        $this->addSql('INSERT INTO tranche_fee(id_tranche, fee_type, fee_comment, fee_rate, fee_recurring, updated, added)  SELECT  id, commission_type, NULL, commission_rate, 0, NULL, NOW() FROM tranche WHERE commission_type IS NOT NULL AND commission_rate IS NOT NULL');
        $this->addSql('ALTER TABLE tranche_fee ADD CONSTRAINT FK_ACF46377B8FAF130 FOREIGN KEY (id_tranche) REFERENCES tranche (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche DROP commission_type, DROP commission_rate');
    }
}
