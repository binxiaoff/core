<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191114171053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-426 (Update projectParticipation values)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE project_participation SET roles = REPLACE(roles, 'DUTY_PROJECT_PARTICIPATION_', '')");
        $this->addSql('UPDATE project_participation SET roles = LOWER(roles)');
    }

    public function down(Schema $schema): void
    {
        $data = $this->connection->fetchAll('SELECT * FROM project_participation');

        foreach ($data as $datum) {
            $roles = json_decode($datum['roles'], true, 512, JSON_THROW_ON_ERROR);
            $roles = array_map(static function ($role) {return 'DUTY_PROJECT_PARTICIPATION_' . mb_strtoupper($role); }, $roles);
            $roles = json_encode($roles, JSON_THROW_ON_ERROR, 512);
            $id    = $datum['id'];
            $this->addSql("UPDATE project_participation SET roles = '{$roles}' WHERE id = {$id}");
        }
    }
}
