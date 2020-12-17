<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200728155723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix group name for CA Charente martitme deux sevres';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE company SET group_name = 'Crédit Agricole' WHERE short_code = 'CM2SE'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE company SET group_name = 'Crédit Agricole' WHERE short_code = 'CM2SE'");
    }
}
