<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210511230925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-3803 Add unicity constraint for AgentMember';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_925D3B7BC80EDDAD6B3CA4B ON agency_agent_member (id_agent, id_user)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_925D3B7BC80EDDAD6B3CA4B ON agency_agent_member');
    }
}
