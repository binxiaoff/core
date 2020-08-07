<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200807084558 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-1938 Remove arranger from organizers';
    }

    public function up(Schema $schema) : void
    {
        $organizers = $this->connection->executeQuery('SELECT * FROM project_organizer WHERE roles LIKE "%\"arranger%"')->fetchAll();

        foreach ($organizers as $organizer) {
            $roles = json_decode($organizer['roles']);
            $organizerId = $organizer['id'];

            $key = array_search('arranger', $roles);
            unset($roles[$key]);

            if (empty ($roles)) {
                $this->addSql("DELETE FROM project_organizer WHERE id = $organizerId");
            } else {
                $roles = $this->connection->quote(json_encode($roles));

                $this->addSql("UPDATE project_organizer SET roles = $roles WHERE id = $organizerId");
            }
        }
    }

    public function down(Schema $schema) : void
    {
    }
}
