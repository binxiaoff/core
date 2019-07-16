<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190715162643 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-245 Rename energy market to Unifergie';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE translations SET translation = "Unifergie" WHERE section = "market-segment" AND name = "energy"');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE translations SET translation = "Énergie" WHERE section = "market-segment" AND name = "energy"');
    }
}
