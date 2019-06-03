<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190529092339 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-184 Display disclaimer explaining beta version limitations';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'TRANSLATIONS'
INSERT INTO translations (locale, section, name, translation, added)
VALUES
  ('fr_FR', 'beta-disclaimer-modal', 'title', 'Version de test', NOW()),
  ('fr_FR', 'beta-disclaimer-modal', 'confirm-button', 'J’ai compris', NOW()),
  ('fr_FR', 'beta-disclaimer-modal', 'content', '<p>Vous êtes sur la version de test (beta) de la plateforme Crédit Agricole Lending Services. Avant de commencer, veuillez prendre connaissance des contraintes et limitations liées à l’utilisation de cette version de développement.</p>
<ul>
    <li>Les données (y compris les documents chargés) seront intégralement effacées à la fin de la période de test.</li>
    <li>Lors de vos tests, des emails de notification seront envoyés aux autres participants.</li>
    <li>De même, vous êtes susceptible de recevoir des emails suite aux tests des autres utilisateurs.</li>
    <li>Chaque entité du groupe dispose d’un seul compte sur la plateforme pour le moment. La gestion de plusieurs utilisateurs par entité est en cours de développement et sera disponible dans quelques semaines.</li>
    <li>Afin de faciliter les tests à plusieurs, il est possible d’utiliser l’adresse email d’une liste de diffusion comme identifiant.</li>
    <li>La signature électronique des documents se fait sans confirmation SMS, les comptes de test DocuSign ne le permettant pas.</li>
    <li>Les données ne correspondent pas nécessairement à des cas réels.</li>
    <li>L’interface est susceptible d’évoluer pendant la période de test.</li>
    <li>Les données peuvent être altérées en raison des développements en cours.</li>
</ul>
<p>Pour toute question ou précision, n’hésitez pas à contacter l’équipe Crédit Agricole Lending Services.</p>', NOW())
TRANSLATIONS
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "beta-disclaimer-modal"');
    }
}
