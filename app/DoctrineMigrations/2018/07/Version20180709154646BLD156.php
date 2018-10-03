<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20180709154646BLD156 extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE translations SET translation = \'Votre capacité à prêter est actuellement limitée. Pour prêter comme vous le souhaitez et bénéficier de conseils personnalisés, nous vous proposons de répondre au questionnaire d’évaluation.\' WHERE section = \'lender-evaluation\' AND name = \'index-no-ongoing-evaluation-text\'');
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE translations SET translation = \'Votre capacité à prêter est actuellement limitée car nous n\'\'avons pas pu vous donner de conseil personnalisé. Pour prêter comme vous le souhaitez et bénéficier de conseils personnalisés, nous vous proposons de répondre au questionnaire d\'\'évaluation.\' WHERE section = \'lender-evaluation\' AND name = \'index-no-ongoing-evaluation-text\'');
    }
}
