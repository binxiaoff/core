<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180904123615BLD297 extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE translations SET translation = \'Modifier\' WHERE section = \'lender-profile\' AND name = \'security-password-section-modify-button\'');
        $this->addSql("UPDATE translations SET translation = 'Il est recommandé d\'utiliser un mot de passe que vous n\'utilisez pas sur d\'autres sites internet.',
                        name = 'security-password-usage-recommandation-message'
                        WHERE section = 'lender-profile' AND name = 'security-password-section-hidden-pwd-placeholder'");
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE translations SET translation = \'Changer mon mot de passe\' WHERE section = \'lender-profile\' AND name = \'security-password-section-modify-button\'');
        $this->addSql('UPDATE translations SET translation = \'************\',
                        name = \'security-password-section-hidden-pwd-placeholder\'
                        WHERE section = \'lender-profile\' AND name = \'security-password-usage-recommandation-message\'');
    }
}
