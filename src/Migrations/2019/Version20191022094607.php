<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191022094607 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-411 Modify mail header to remove the "gulp" assets package';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $header = <<<'TWIG'
<p>
    <img src="{{ asset('images/logo/logo-and-type-245x52@2x.png') }}" alt="Crédit Agricole Lending Services" width="209" height="44">
</p>
<h2>{{ title|default() }}</h2>
TWIG;
        $content = $this->connection->quote($header);

        $this->addSql("UPDATE mail_header SET content = {$content} WHERE id = 1");
        $this->addSql('RENAME TABLE clients_status TO client_status');
        $this->addSql('ALTER TABLE client_status RENAME INDEX idx_clients_status_id_client TO idx_client_status_id_client');
        $this->addSql('ALTER TABLE client_status RENAME INDEX idx_clients_status_status TO idx_client_status_status');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $header = <<<'TWIG'
<p>
    <img src="{{ asset('images/logo/logo-and-type-245x52@2x.png', 'gulp') }}" alt="Crédit Agricole Lending Services" width="209" height="44">
</p>
<h2>{{ title|default() }}</h2>
TWIG;
        $content = $this->connection->quote($header);

        $this->addSql("UPDATE mail_header SET content = {$content} WHERE id = 1");
        $this->addSql('ALTER TABLE client_status RENAME INDEX idx_client_status_status TO idx_clients_status_status');
        $this->addSql('ALTER TABLE client_status RENAME INDEX idx_client_status_id_client TO idx_clients_status_id_client');
        $this->addSql('RENAME TABLE client_status TO clients_status');
    }
}
