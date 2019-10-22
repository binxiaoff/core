<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191007094502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-394';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO mail_template (name, locale, id_header, id_footer, id_layout, sender_name, sender_email, subject, content, added)
 VALUES ('request-rights-new-staff', 'fr_FR', 1, 1, 1, 'Crédit Agricole Lending Services', 'contact@ca-lendingservices.com', 'Une personne de votre entité a été invitée sur un projet', '<p>Bonjour {{ firstName }},</p>
<p>{{ guest }} a été invité(e) sur le projet {{ projectName }}, vous pouvez lui ajouter des droits plus larges.</p>
', '2019-10-07 11:44:44')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM mail_templates WHERE type = 'request-rights-new-staff'");
    }
}
