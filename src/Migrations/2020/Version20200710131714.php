<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

final class Version20200710131714 extends AbstractMigration
{
    private const DATA = [
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Alsace Vosges',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'ALVO',
            'bankCode'      => 17206,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Atlantique Vendée',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'ATVD',
            'bankCode'      => 14706,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Brie Picardie',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'BRPI',
            'bankCode'      => 18706,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Centre Loire',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'CENL',
            'bankCode'      => 14806,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Centre-Est',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'CEST',
            'bankCode'      => 17806,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Charente-Maritime Deux-Sèvres',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'CMSE',
            'bankCode'      => 11706,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Charente-Périgord (Crédit agricole CharentePérigord)',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'CHPE',
            'bankCode'      => 12406,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse régionale de crédit agricole mutuel d'Alpes-Provence",
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'CAPR',
            'bankCode'      => 11306,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse régionale de crédit agricole mutuel d'Aquitaine",
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'AQTN',
            'bankCode'      => 13306,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel de Centre France - Crédit agricole Centre France (3ème du nom)',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'CENF',
            'bankCode'      => 16806,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel de Champagne-Bourgogne',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'CHBO',
            'bankCode'      => 11006,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel de Franche-Comté',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'FRAC',
            'bankCode'      => 12506,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel de la Corse',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'CORS',
            'bankCode'      => 12006,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel de la Guadeloupe',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'GUAD',
            'bankCode'      => 14006,
            'applicableVat' => 'overseas',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel de la Martinique et de la Guyane',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'MART',
            'bankCode'      => 19806,
            'applicableVat' => 'overseas',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel de la Réunion',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'REUN',
            'bankCode'      => 19906,
            'applicableVat' => 'overseas',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel de la Touraine et du Poitou',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'TPOI',
            'bankCode'      => 19406,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse régionale de crédit agricole mutuel de l'Anjou et du Maine",
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'ANMA',
            'bankCode'      => 17906,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel de Lorraine',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'LORR',
            'bankCode'      => 16106,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel de Normandie',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'NORM',
            'bankCode'      => 16606,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse régionale de crédit agricole mutuel de Paris et d'Ile-de-France",
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'IDFR',
            'bankCode'      => 18206,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel de Toulouse et du Midi toulousain',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'TOUL',
            'bankCode'      => 13106,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse régionale de crédit agricole mutuel des Côtes-d'Armor",
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'CODA',
            'bankCode'      => 12206,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel des Savoie - Crédit agricole des Savoie',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'SAVO',
            'bankCode'      => 18106,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse régionale de crédit agricole mutuel d'Ille-et-Vilaine",
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'ILVI',
            'bankCode'      => 13606,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel du Centre Ouest',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'COUE',
            'bankCode'      => 19506,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel du Finistère',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'FINI',
            'bankCode'      => 12906,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel du Languedoc',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'LANG',
            'bankCode'      => 13506,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel du Morbihan',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'MORB',
            'bankCode'      => 16006,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel du Nord Est',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'NEST',
            'bankCode'      => 10206,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Loire - Haute-Loire',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'L&HL',
            'bankCode'      => 14506,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Nord de France',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'NORF',
            'bankCode'      => 16706,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Nord Midi-Pyrénées',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'NMPY',
            'bankCode'      => 11206,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Normandie-Seine',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'NORS',
            'bankCode'      => 18306,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse régionale de crédit agricole mutuel Provence-Côte d'Azur (Alpes de HauteProvence-Alpes-maritimes-Var)",
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'PRCA',
            'bankCode'      => 19106,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Pyrénées-Gascogne',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'PYGA',
            'bankCode'      => 16906,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Sud Rhône-Alpes',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'SRAL',
            'bankCode'      => 13906,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Sud-Méditerranée (Ariège et PyrénéesOrientales)',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'SMED',
            'bankCode'      => 17106,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Caisse régionale de crédit agricole mutuel Val de France',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'VALF',
            'bankCode'      => 14406,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'CACIB',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'CIB',
            'bankCode'      => 31489,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit du Maroc',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'CDM',
            'bankCode'      => 11778,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Foncaris',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => null,
            'bankCode'      => 28860,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Agricole S.A.',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'CASA',
            'bankCode'      => 30006,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit lyonnais',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => 'LCL',
            'bankCode'      => 30002,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit agricole leasing',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => null,
            'bankCode'      => 13210,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'LixxBail',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => null,
            'bankCode'      => 13150,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Chalus',
            'groupName'     => 'Crédit Agricole',
            'shortCode'     => null,
            'bankCode'      => 10188,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Société Générale',
            'groupName'     => '',
            'shortCode'     => 'SOGE',
            'bankCode'      => 30003,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'BNP Paribas',
            'groupName'     => '',
            'shortCode'     => 'BNP',
            'bankCode'      => 30004,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'La banque postale',
            'groupName'     => '',
            'shortCode'     => 'LBP',
            'bankCode'      => 20041,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'HSBC',
            'groupName'     => '',
            'shortCode'     => 'HSBC',
            'bankCode'      => 30056,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Groupe Crédit du Nord',
            'groupName'     => '',
            'shortCode'     => 'GCDN',
            'bankCode'      => 30076,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit du Nord et Société marseillaise de crédit',
            'groupName'     => '',
            'shortCode'     => 'CMDC',
            'bankCode'      => 30077,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Barclays',
            'groupName'     => '',
            'shortCode'     => 'BARC',
            'bankCode'      => 24599,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Neuflize OBC',
            'groupName'     => '',
            'shortCode'     => 'OBC',
            'bankCode'      => 30788,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'ABN AMRO',
            'groupName'     => '',
            'shortCode'     => 'ABN',
            'bankCode'      => 18739,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'RABOBANK',
            'groupName'     => '',
            'shortCode'     => 'RABO',
            'bankCode'      => 10218,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Monte Paschi',
            'groupName'     => '',
            'shortCode'     => 'PASCHI',
            'bankCode'      => 30478,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'FORTIS BANQUE',
            'groupName'     => '',
            'shortCode'     => 'FORTIS',
            'bankCode'      => 30488,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Cooperatif',
            'groupName'     => 'BPCE',
            'shortCode'     => 'CCOP',
            'bankCode'      => 42559,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Natixis',
            'groupName'     => 'BPCE',
            'shortCode'     => 'NATIXIS',
            'bankCode'      => 30007,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'La banque Palatine',
            'groupName'     => 'BPCE',
            'shortCode'     => 'BPAL',
            'bankCode'      => 40978,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Banque régionale d'escompte et de dépôts",
            'groupName'     => 'BPCE',
            'shortCode'     => 'BRED',
            'bankCode'      => 10107,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Populaire',
            'groupName'     => 'BPCE',
            'shortCode'     => 'BP',
            'bankCode'      => '16188-1',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Populaire Alsace Lorraine Champagne',
            'groupName'     => 'BPCE',
            'shortCode'     => 'BPALC',
            'bankCode'      => 17607,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Populaire Aquitaine Centre Atlantique',
            'groupName'     => 'BPCE',
            'shortCode'     => 'BPACA',
            'bankCode'      => 13607,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Populaire Bourgogne franche comte',
            'groupName'     => 'BPCE',
            'shortCode'     => 'BPBFC',
            'bankCode'      => 10807,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Populaire Grand Ouest',
            'groupName'     => 'BPCE',
            'shortCode'     => 'BPGO',
            'bankCode'      => 16707,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Populaire Auvergne Rhône Alpes',
            'groupName'     => 'BPCE',
            'shortCode'     => 'BPARA',
            'bankCode'      => 16807,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Populaire du Nord',
            'groupName'     => 'BPCE',
            'shortCode'     => 'BPDN',
            'bankCode'      => 13507,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Populaire du Sud',
            'groupName'     => 'BPCE',
            'shortCode'     => 'BPDS',
            'bankCode'      => '16607-1',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Populaire Méditéranée',
            'groupName'     => 'BPCE',
            'shortCode'     => 'BPM',
            'bankCode'      => '16607-2',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Populaire Occitane',
            'groupName'     => 'BPCE',
            'shortCode'     => 'BPO',
            'bankCode'      => 17807,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Populaire Rives de Paris',
            'groupName'     => 'BPCE',
            'shortCode'     => 'BPRP',
            'bankCode'      => 10207,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Banque Populaire Val de France',
            'groupName'     => 'BPCE',
            'shortCode'     => 'BPVF',
            'bankCode'      => 18707,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CDE',
            'bankCode'      => '16188-2',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne Aquitaine Poitou-Charentes",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CEPC',
            'bankCode'      => 13335,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne Bretagne Pays de Loire",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CEBPL',
            'bankCode'      => 14445,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne CEPAC",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CEPAC',
            'bankCode'      => 11315,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne Cote d'Azur",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CECA',
            'bankCode'      => 18315,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne d'Auvergne et du Limousin",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CEAL',
            'bankCode'      => 18715,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne de Bourgogne Franche-Comte",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CEBFC',
            'bankCode'      => 12135,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne de Midi-Pyrenees",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CEMP',
            'bankCode'      => 13135,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne Grand Est Europe",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CEGEE',
            'bankCode'      => 16705,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne Hauts de France",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CEHF',
            'bankCode'      => 16275,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne Ile-de-France",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CEIDF',
            'bankCode'      => 17515,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne Languedoc-Roussillon",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CELR',
            'bankCode'      => 13485,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne Loire Drome Ardeche",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CELDA',
            'bankCode'      => 14265,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne Loire-Centre",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CELC',
            'bankCode'      => 14505,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne Normandie",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CEN',
            'bankCode'      => 11425,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => "Caisse d'Epargne Rhône Alpes",
            'groupName'     => 'BPCE',
            'shortCode'     => 'CERA',
            'bankCode'      => 13825,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Le crédit Industriel et Commercial',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CIC',
            'bankCode'      => 30066,
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CM',
            'bankCode'      => '15589-1',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel Anjou',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMAN',
            'bankCode'      => '10278-1',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel du Centre',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMC',
            'bankCode'      => '10278-2',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel Centre-Est Europe',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMCEE',
            'bankCode'      => '10278-3',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel Dauphine Vivarais',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMDV',
            'bankCode'      => '10278-4',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel Ile de France',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMIDF',
            'bankCode'      => '10278-5',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel Loire-Atlantique, Centre-Ouest',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMLACO',
            'bankCode'      => '10278-6',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel Massif Central',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMMC',
            'bankCode'      => '10278-7',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel Méditéranéen',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMM',
            'bankCode'      => '10278-8',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel Midi-Atlantique',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMMA',
            'bankCode'      => '10278-9',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel Normandie',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMN',
            'bankCode'      => '10278-10',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel Savoie Mont Blanc',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMSMB',
            'bankCode'      => '10278-11',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel Sud-Est',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMSE',
            'bankCode'      => '10278-12',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel Arkéa',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMAR',
            'bankCode'      => '15589-2',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel de Bretagne',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMB',
            'bankCode'      => '15589-3',
            'applicableVat' => 'metropolitan',
        ],
        [
            'name'          => 'Crédit Mutuel du Sud-Ouest',
            'groupName'     => 'Crédit Mutuel',
            'shortCode'     => 'CMSO',
            'bankCode'      => '15589-4',
            'applicableVat' => 'metropolitan',
        ],
    ];

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1912 Update company data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company CHANGE short_code short_code VARCHAR(10) DEFAULT NULL');

        foreach (static::DATA as $datum) {
            $name          = $datum['name'];
            $groupName     = $datum['groupName'];
            $shortCode     = $datum['shortCode'];
            $bankCode      = $datum['bankCode'];
            $applicableVat = $datum['applicableVat'];

            $id = null;

            if ($shortCode) {
                $id = $this->connection->query("SELECT id FROM company WHERE short_code = '{$shortCode}'")->fetch();
            }

            if (!$id) {
                $id = $this->connection->query("SELECT id FROM company WHERE bank_code = '{$bankCode}'")->fetch();
            }

            $shortCode = $shortCode ? "'{$shortCode}'" : 'NULL';
            $name      = $this->connection->quote($name);
            $groupName = $groupName ? $this->connection->quote($groupName) : 'NULL';

            if ($id) {
                $id = $id['id'];
                $this->addSql("UPDATE company SET name = {$name},  short_code = {$shortCode},  group_name = {$groupName}, applicable_vat = '{$applicableVat}', bank_code = '{$bankCode}' WHERE id = {$id} ");
            } else {
                $companyPublicId = (string) (Uuid::uuid4());
                $this->addSql("INSERT INTO company(name, added, short_code, public_id, group_name, applicable_vat, bank_code) 
                            VALUES ({$name}, NOW(), {$shortCode}, '{$companyPublicId}', {$groupName}, '{$applicableVat}', '{$bankCode}' )");

                $statusPublicId = (string) (Uuid::uuid4());
                $this->addSql("INSERT INTO company_status SELECT NULL as id, (SELECT MAX(id) from company) as id_company, 10 as status, NOW() as added, '{$statusPublicId}' as public_id");
                $this->addSql("UPDATE company SET id_current_status = (SELECT MAX(id) from company_status) WHERE public_id = '{$companyPublicId}'");
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('This migration only updates data');
    }
}
