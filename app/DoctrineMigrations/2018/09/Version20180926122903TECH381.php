<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180926122903TECH381 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');
        $this->addSql(<<<'TRANSLATION'
UPDATE translations 
SET translation = 'Vous souhaitez emprunter la somme de <strong class="c-t1"><span class="ui-esim-output-cost"></span>&nbsp;€</strong> sur une durée de <strong class="c-t1"><span class="ui-esim-output-duration"></span>&nbsp;mois</strong>, dans le but <span class="ui-esim-output-reason c-t1"></span>.'
WHERE section = 'home-borrower' AND name = 'simulator-step-2-text-for-motive'
TRANSLATION
);

    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');
        $this->addSql(<<<'TRANSLATION'
UPDATE translations 
SET translation = 'Vous souhaitez emprunter la somme de <strong class="ui-esim-output-cost c-t1">&nbsp;€</strong> sur une durée de <strong class="ui-esim-output-duration c-t1"> mois</strong>, dans le but <strong class="ui-esim-output-reason c-t1"></strong>.'
WHERE section = 'home-borrower' AND name = 'simulator-step-2-text-for-motive'
TRANSLATION
        );
    }
}
