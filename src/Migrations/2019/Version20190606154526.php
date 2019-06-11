<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190606154526 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-190 Change loan/repayment types';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tranche ADD loan_type VARCHAR(30) NOT NULL AFTER name');

        $this->addSql('UPDATE translations SET translation = "Maturité" WHERE section = "tranche-form" AND name = "maturity"');
        $this->addSql('UPDATE translations SET translation = "Périodicité capital" WHERE section = "tranche-form" AND name = "capital-periodicity"');
        $this->addSql('UPDATE translations SET translation = "Périodicité intérêts" WHERE section = "tranche-form" AND name = "interest-periodicity"');
        $this->addSql('UPDATE translations SET translation = "Modalités de remb." WHERE section = "tranche-form" AND name = "repayment-type"');
        $this->addSql('UPDATE translations SET translation = "Taux d’intérêt" WHERE section = "lending-form" AND name = "margin"');
        $this->addSql('UPDATE translations SET translation = "Floor" WHERE section = "lending-form" AND name = "floor"');

        $this->addSql('DELETE FROM translations WHERE section = "repayment-type"');

        $this->addSql(
            <<<'TRANSLATIONS'
INSERT INTO translations (locale, section, name, translation, added)
VALUES
  ('fr_FR', 'tranche-form', 'maturity-unit', '(mois)', NOW()),
  ('fr_FR', 'tranche-form', 'capital-periodicity-unit', '(mois)', NOW()),
  ('fr_FR', 'tranche-form', 'interest-periodicity-unit', '(mois)', NOW()),
  ('fr_FR', 'tranche-form', 'loan-type', 'Type', NOW()),
  ('fr_FR', 'lending-form', 'floor-unit', '(%)', NOW()),
  ('fr_FR', 'lending-form', 'margin-unit', '(%)', NOW()),
  ('fr_FR', 'loan-type', 'term_loan', 'Term loan', NOW()),
  ('fr_FR', 'loan-type', 'revolving_credit', 'RCF', NOW()),
  ('fr_FR', 'loan-type', 'capex', 'CAPEX', NOW()),
  ('fr_FR', 'repayment-type', 'amortizable', 'Amortissable', NOW()),
  ('fr_FR', 'repayment-type', 'balloon', 'Balloon', NOW()),
  ('fr_FR', 'repayment-type', 'bullet', 'Bullet', NOW())
TRANSLATIONS
        );
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tranche DROP loan_type');

        $this->addSql('UPDATE translations SET translation = "Maturité (mois)" WHERE section = "tranche-form" AND name = "maturity"');
        $this->addSql('UPDATE translations SET translation = "Périodicité capital (mois)" WHERE section = "tranche-form" AND name = "capital-periodicity"');
        $this->addSql('UPDATE translations SET translation = "Périodicité intérêts (mois)" WHERE section = "tranche-form" AND name = "interest-periodicity"');
        $this->addSql('UPDATE translations SET translation = "Type" WHERE section = "tranche-form" AND name = "repayment-type"');
        $this->addSql('UPDATE translations SET translation = "Marge en %" WHERE section = "lending-form" AND name = "margin"');
        $this->addSql('UPDATE translations SET translation = "Floorés à en %" WHERE section = "lending-form" AND name = "floor"');

        $this->addSql('DELETE FROM translations WHERE section = "tranche-form" AND name IN ("maturity-unit", "capital-periodicity-unit", "interest-periodicity-unit", "loan-type")');
        $this->addSql('DELETE FROM translations WHERE section = "lending-form" AND name IN ("floor-unit", "margin-unit")');
        $this->addSql('DELETE FROM translations WHERE section = "loan-type"');
        $this->addSql('DELETE FROM translations WHERE section = "repayment-type"');

        $this->addSql(
            <<<'TRANSLATIONS'
INSERT INTO translations (locale, section, name, translation, added)
VALUES
  ('fr_FR', 'repayment-type', 'repayment_type_amortizing_fixed_payment', 'Échéance fixe', NOW()),
  ('fr_FR', 'repayment-type', 'repayment_type_amortizing_fixed_capital', 'Capital fixe', NOW()),
  ('fr_FR', 'repayment-type', 'repayment_type_non_amortizing_in_fine', 'In Fine', NOW()),
  ('fr_FR', 'repayment-type', 'repayment_type_revolving_credit', 'CAPEX', NOW())
TRANSLATIONS
        );
    }
}
