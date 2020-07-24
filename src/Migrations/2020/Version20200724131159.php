<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Ramsey\Uuid\Uuid;

final class Version20200724131159 extends AbstractMigration
{
    private const DATA = [
        'CA Lending Services' => 'CALS',
        'CA Alpes Provence' => 'CAPR',
        'CA Alsace Vosges' => 'ALVO',
        'CA Anjou et Maine' => 'ANMA',
        'CA Aquitaine' => 'AQTN',
        'CA Atlantique Vendée' => 'ATVD',
        'CA Brie Picardie' => 'BRPI',
        'CA Centre-Est' => 'CEST',
        'CA Centre France' => 'CENF',
        'CA Centre Loire' => 'CENL',
        'CA Centre Ouest' => 'COUE',
        'CA Champagne-Bourgogne' => 'CHBO',
        'CA Charente Maritime Deux-Sèvres' => 'CMSE',
        'CA Charente-Périgord' => 'CHPE',
        'CA Corse' => 'CORS',
        'CA Côtes d’Armor' => 'CODA',
        'CA Normandie' => 'NORM',
        'CA des Savoie' => 'SAVO',
        'CA Finistère' => 'FINI',
        'CA Franche-Comté' => 'FRAC',
        'CA Guadeloupe' => 'GUAD',
        'CA Ille-et-Vilaine' => 'ILVI',
        'CA Languedoc' => 'LANG',
        'CA Loire Haute-Loire' => 'L&HL',
        'CA Lorraine' => 'LORR',
        'CA Martinique-Guyane' => 'MART',
        'CA Morbihan' => 'MORB',
        'CA Nord de France' => 'NORF',
        'CA Nord Est' => 'NEST',
        'CA Nord Midi Pyrénées' => 'NMPY',
        'CA Normandie-Seine' => 'NORS',
        'CA Paris et Ile-de-France' => 'IDFR',
        'CA Provence Côte d’Azur' => 'PRCA',
        'CA Pyrénées Gascogne' => 'PYGA',
        'CA La Réunion' => 'REUN',
        'CA Sud Rhône Alpes' => 'SRAL',
        'CA Sud Méditerranée' => 'SMED',
        'CA Toulouse 31' => 'TOUL',
        'CA Touraine Poitou' => 'TPOI',
        'CA Val de France' => 'VALF',
        'LCL' => 'LCL',
        'CA-CIB' => 'CIB',
        'Unifergie' => 'UNFG',
    ];
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'CALS-1978 Add businessName field to Company';
    }

    /**
     * @param Schema $schema
     * @throws Exception
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE company ADD business_name VARCHAR(300) NOT NULL');
        $this->addSql('UPDATE company SET business_name = name WHERE 1 = 1');

        foreach (static::DATA as $name => $shortCode) {
            $this->addSql("UPDATE company SET name = '$name' where short_code = '$shortCode'");
        }

        $this->addSql("UPDATE company SET siren = '824339097', business_name = 'Caisse régionale de crédit agricole mutuel Charente-Maritime Deux-Sèvres', bank_code = '11706' WHERE short_code = 'CMSE'");
        $this->addSql("UPDATE company SET short_code = 'CM2SE' WHERE short_code ='CMSE'");

        $uuid = Uuid::uuid4();
        $this->addSql("INSERT INTO company(name, siren, added, short_code, public_id, id_current_status, group_name, applicable_vat, bank_code, business_name)
                            VALUES ('Crédit Mutuel Sud-Est', '778147454', NOW(), 'CMSE', '$uuid', null, 'Crédit Mutuel', 'metropolitan', '10278-12', 'Crédit Mutuel Sud-Est')");

        $this->addSql('ALTER TABLE company RENAME COLUMN name TO display_name');
        $this->addSql('ALTER TABLE company RENAME COLUMN business_name TO company_name');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE company RENAME COLUMN display_name TO name');
        $this->addSql('ALTER TABLE company DROP company_name');
    }
}
