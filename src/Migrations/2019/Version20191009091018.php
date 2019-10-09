<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191009091018 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'TECH-148 (Update FeeType type field type to string)';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE loan_fee CHANGE fee_type fee_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE bid_fee CHANGE fee_type fee_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE project_fee CHANGE fee_type fee_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE tranche_fee CHANGE fee_type fee_type VARCHAR(255) NOT NULL');

        foreach (['project', 'tranche'] as $entity) {
            $table = $this->getTableName($entity);
            foreach ($this->getExpectedFeeTypesAfterMigration($entity) as $integerCode => $stringCode) {
                $this->addSql("UPDATE {$table} SET fee_type = '{$stringCode}' WHERE CAST(fee_type AS CHAR) = CAST({$integerCode} AS CHAR)");
            }
            $this->addSql("UPDATE translations SET name = REPLACE(name, '{$entity}_fee_type_', '') WHERE section = 'fee-type' and locale = 'fr_FR'");
        }
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        foreach (['project', 'tranche'] as $entity) {
            if (empty($feeTypes = $this->getFeeTypes($entity)) || array_values($this->{'getPrevious' . ucfirst($entity) . 'FeeTypes'}()) !== array_values($feeTypes)) {
                $this->throwIrreversibleMigrationException("Please check the code of the class {$this->getClassName($entity)} as it is not in sync with the wanted database state");
            }

            $table = $this->getTableName($entity);

            foreach ($feeTypes as $stringCode => $integerCode) {
                $this->addSql("UPDATE {$table} SET fee_type = {$integerCode} WHERE '{$stringCode}' LIKE CONCAT('%', feeType, '%')");

                $this->addSql("UPDATE translations SET name = CONCAT('{$entity}_fee_type_', name) WHERE section = 'fee-type' and locale = 'fr_FR'");
            }
        }

        $this->addSql('ALTER TABLE bid_fee CHANGE fee_type fee_type SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE loan_fee CHANGE fee_type fee_type SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE project_fee CHANGE fee_type fee_type SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE tranche_fee CHANGE fee_type fee_type SMALLINT NOT NULL');
    }

    /**
     * @param $entity
     *
     * @return string
     */
    private function getTableName($entity): string
    {
        return $entity . '_fee';
    }

    /**
     * @param $entity
     *
     * @return array|mixed
     */
    private function getFeeTypes($entity)
    {
        $class = $this->getClassName($entity);

        if (false === method_exists($class, 'getFeeTypes')) {
            return [];
        }

        return call_user_func([$class, 'getFeeTypes']);
    }

    /**
     * @param string $entity
     *
     * @return string
     */
    private function getClassName(string $entity): string
    {
        return 'Unilend\Entity\\' . ucfirst($entity) . 'Fee';
    }

    /**
     * @return array
     */
    private function getPreviousTrancheFeeTypes(): array
    {
        return [
            'TRANCHE_FEE_TYPE_NON_UTILISATION' => 1,
            'TRANCHE_FEE_TYPE_COMMITMENT'      => 2,
            'TRANCHE_FEE_TYPE_UTILISATION'     => 3,
        ];
    }

    private function getPreviousProjectFeeTypes(): array
    {
        return [
            'PROJECT_FEE_TYPE_PARTICIPATION' => 1,
        ];
    }

    /**
     * @param string $entity
     *
     * @return array
     */
    private function getExpectedFeeTypesAfterMigration(string $entity): array
    {
        $previousFeeTypes = $this->{'getPrevious' . ucfirst($entity) . 'FeeTypes'}();

        $nextFeeTypes = array_flip($previousFeeTypes);

        return array_map(
            static function ($feeType) use ($entity) {
                return str_replace(mb_strtolower($entity . '_FEE_TYPE_'), '', mb_strtolower($feeType));
            },
            $nextFeeTypes
        );
    }
}
