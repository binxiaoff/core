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
                $roles = $this->connection->quote(json_encode(array_values($roles)));

                $this->addSql("UPDATE project_organizer SET roles = $roles WHERE id = $organizerId");
            }
        }
    }

    public function down(Schema $schema) : void
    {
        $projects = $this->connection->executeQuery('SELECT * FROM project')->fetchAll();

        foreach ($projects as $project) {
            $arrangerOrganizer = $this->connection->executeQuery("SELECT * FROM project_organizer WHERE id_project = {$project['id']} AND id_company = {$project['id_company_submitter']}")->fetch();

            if ($arrangerOrganizer) {
                $roles = json_decode($arrangerOrganizer['roles']);
                $roles[] = 'arranger';
                $roles = $this->connection->quote(json_encode($roles));
                $this->addSql("UPDATE project_organizer SET roles = $roles WHERE id = ${arrangerOrganizer['id']}");
            } else {
                $role = "'[\"arranger\"]'";
                $this->addSql("INSERT INTO project_organizer (roles, id_project, id_company, added_by, added) VALUES ($role, ${project["id"]}, ${project["id_company_submitter"]}, ${project["id_company_submitter"]}, NOW())");
            }
        }
    }
}
